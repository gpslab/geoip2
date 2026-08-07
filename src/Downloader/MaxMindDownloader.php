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
use splitbrain\PHPArchive\Tar;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MaxMind downloader.
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
    private const PERMISSIONS = 0755;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Permissions of the downloaded database.
     *
     * @var int
     */
    private $permissions;

    /**
     * URL of the proxy server to download a database through.
     *
     * @var string|null
     */
    private $proxy;

    /**
     * @param Filesystem      $fs
     * @param LoggerInterface $logger
     * @param int             $permissions
     * @param string|null     $proxy
     */
    public function __construct(Filesystem $fs, LoggerInterface $logger, int $permissions = self::PERMISSIONS, ?string $proxy = null)
    {
        $this->fs = $fs;
        $this->logger = $logger;
        $this->permissions = $permissions;
        $this->proxy = $proxy;
    }

    /**
     * @param string $url
     * @param string $target
     */
    public function download(string $url, string $target): void
    {
        $id = uniqid('', true);
        $tmp_zip = sprintf('%s/%s_GeoLite2.tar.gz', sys_get_temp_dir(), $id);
        $tmp_unzip = sprintf('%s/%s_GeoLite2.tar', sys_get_temp_dir(), $id);
        $tmp_untar = sprintf('%s/%s_GeoLite2', sys_get_temp_dir(), $id);

        // remove old files and folders for correct overwrite it
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $this->logger->debug(sprintf('Beginning download of file %s', $url));

        if ($this->proxy === null) {
            $this->fs->copy($url, $tmp_zip, true);
        } else {
            $this->logger->debug(sprintf('Download through the %s proxy', $this->proxy));

            $this->copyThroughProxy($url, $tmp_zip);
        }

        $this->logger->debug(sprintf('Download complete to %s', $tmp_zip));

        $this->fs->mkdir(dirname($target), 0755);

        if (class_exists(Tar::class)) {
            $this->logger->debug(sprintf('Extracting archive file to %s', $tmp_untar));

            // extract tar.gz archive
            $tar = new Tar();
            $tar->open($tmp_zip);
            $tar->extract($tmp_untar);
            $tar->close();
            unset($tar);
        } else {
            $this->logger->debug(sprintf('De-compressing file to %s', $tmp_unzip));

            // decompress gz file
            $zip = new \PharData($tmp_zip);
            $tar = $zip->decompress();

            $this->logger->debug('Decompression complete');
            $this->logger->debug(sprintf('Extract tar file to %s', $tmp_untar));

            // extract tar archive
            $tar->extractTo($tmp_untar);
            unset($zip, $tar);
        }

        $this->logger->debug('Tar archive extracted');

        // find database in archive
        $database = '';
        $files = glob(sprintf('%s/**/*.mmdb', $tmp_untar)) ?: [];
        foreach ($files as $file) {
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
        $this->fs->chmod($target, $this->permissions);
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $this->logger->debug(sprintf('Database moved to %s', $target));
    }

    /**
     * Filesystem::copy() opens the source with the default stream context, so a proxy can not be given to it.
     *
     * @param string $url
     * @param string $target
     */
    private function copyThroughProxy(string $url, string $target): void
    {
        $context = stream_context_create(['http' => $this->createProxyContextOptions()]);

        $source = @fopen($url, 'r', false, $context);

        if ($source === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $url, $target), 0, null, $url);
        }

        $destination = @fopen($target, 'w');

        if ($destination === false) {
            fclose($source);

            throw new IOException(sprintf('Failed to copy "%s" to "%s" because target file could not be opened for writing.', $url, $target), 0, null, $url);
        }

        $copied = @stream_copy_to_stream($source, $destination);

        fclose($source);
        fclose($destination);

        if ($copied === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s".', $url, $target), 0, null, $url);
        }
    }

    /**
     * Build the HTTP stream context options for the configured proxy.
     *
     * PHP expects a transport in the proxy address, not an application protocol, and sends the credentials
     * in the Proxy-Authorization header, they can not be a part of the address.
     *
     * @return array<string, string|bool>
     */
    private function createProxyContextOptions(): array
    {
        $proxy = (string) $this->proxy;
        $scheme = (string) parse_url($proxy, PHP_URL_SCHEME);
        $host = (string) parse_url($proxy, PHP_URL_HOST);
        $port = parse_url($proxy, PHP_URL_PORT);
        $user = parse_url($proxy, PHP_URL_USER);
        $password = parse_url($proxy, PHP_URL_PASS);

        $transport = $scheme === 'https' || $scheme === 'ssl' ? 'ssl' : 'tcp';

        $options = [
            'proxy' => sprintf('%s://%s%s', $transport, $host, is_int($port) ? ':'.$port : ''),
            // a proxy expects the absolute URI in the request line of a plain HTTP request
            'request_fulluri' => true,
        ];

        if (is_string($user)) {
            $credentials = rawurldecode($user).':'.(is_string($password) ? rawurldecode($password) : '');
            $options['header'] = 'Proxy-Authorization: Basic '.base64_encode($credentials);
        }

        return $options;
    }
}
