<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Downloader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MaxMind downloader
 *
 * Expect GeoIP2 archive from https://download.maxmind.com/ with structure:
 *
 * GeoLite2-City.tar.gz
 *  - GeoLite2-City_20200114
 *    - COPYRIGHT.txt
 *    - GeoLite2-City.mmdb
 *    - LICENSE.txt
 *    - README.txt
 */
class MaxMindDownloader implements Downloader
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $tmp_zip;

    /**
     * @var string
     */
    private $tmp_unzip;

    /**
     * @var string
     */
    private $tmp_untar;

    /**
     * @param Filesystem $fs
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $fs, LoggerInterface $logger)
    {
        $this->fs = $fs;
        $this->logger = $logger;
        $this->tmp_zip = sys_get_temp_dir().'/GeoLite2.tar.gz';
        $this->tmp_unzip = sys_get_temp_dir().'/GeoLite2.tar';
        $this->tmp_untar = sys_get_temp_dir().'/GeoLite2';
    }

    /**
     * @param string $url
     * @param string $target
     */
    public function download(string $url, string $target): void
    {
        // remove old files and folders for correct overwrite it
        $this->fs->remove([$this->tmp_zip, $this->tmp_unzip, $this->tmp_untar]);

        $this->logger->debug(sprintf('Beginning download of file %s', $url));

        $this->fs->copy($url, $this->tmp_zip);

        $this->logger->debug(sprintf('Download complete to %s', $this->tmp_zip));
        $this->logger->debug(sprintf('De-compressing file to %s', $this->tmp_unzip));

        $this->fs->mkdir(dirname($target), 0755);

        // decompress gz file
        $phar = new \PharData($this->tmp_zip);
        $phar->decompress();

        $this->logger->debug('Decompression complete');
        $this->logger->debug(sprintf('Extract tar file to %s', $this->tmp_untar));

        // extract tar archive
        $phar = new \PharData($this->tmp_unzip);
        $phar->extractTo($this->tmp_untar);

        $this->logger->debug('Tar archive extracted');

        // find database in archive
        $database = '';
        foreach (glob(sprintf('%s/**/*.mmdb', $this->tmp_untar)) as $file) {
            // expected something like that "GeoLite2-City_20200114"
            if (preg_match('/(?<database>[^\/]+)_(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})/', $file, $match)) {
                $this->logger->debug(sprintf(
                    'Found %s database updated at %s-%s-%s in %s',
                    $match['database'],
                    $match['year'],
                    $match['month'],
                    $match['day'],
                    $file
                ));
            }

            $database = $file;
        }

        if (!$database) {
            throw new \RuntimeException('Not found GeoLite2 database in archive.');
        }

        $this->fs->copy($database, $target, true);
        $this->fs->chmod($target, 0755);
        $this->fs->remove([$this->tmp_zip, $this->tmp_unzip, $this->tmp_untar]);

        $this->logger->debug(sprintf('Database moved to %s', $target));
    }
}
