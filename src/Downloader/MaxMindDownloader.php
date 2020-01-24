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
     * @param Filesystem $fs
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $fs, LoggerInterface $logger)
    {
        $this->fs = $fs;
        $this->logger = $logger;
    }

    /**
     * @param string $url
     * @param string $target
     */
    public function download(string $url, string $target): void
    {
        $id = uniqid('', false);
        $tmp_zip = sprintf('%s/%s_GeoLite2.tar.gz', sys_get_temp_dir(), $id);
        $tmp_unzip = sprintf('%s/%s_GeoLite2.tar', sys_get_temp_dir(), $id);
        $tmp_untar = sprintf('%s/%s_GeoLite2', sys_get_temp_dir(), $id);

        // remove old files and folders for correct overwrite it
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $this->logger->debug(sprintf('Beginning download of file %s', $url));

        $this->fs->copy($url, $tmp_zip, true);

        $this->logger->debug(sprintf('Download complete to %s', $tmp_zip));
        $this->logger->debug(sprintf('De-compressing file to %s', $tmp_unzip));

        $this->fs->mkdir(dirname($target), 0755);

        // decompress gz file
        $zip = new \PharData($tmp_zip);
        $tar = $zip->decompress();

        $this->logger->debug('Decompression complete');
        $this->logger->debug(sprintf('Extract tar file to %s', $tmp_untar));

        // extract tar archive
        $tar->extractTo($tmp_untar);

        $this->logger->debug('Tar archive extracted');

        // find database in archive
        $database = '';
        foreach (glob(sprintf('%s/**/*.mmdb', $tmp_untar)) as $file) {
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
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $this->logger->debug(sprintf('Database moved to %s', $target));
    }
}
