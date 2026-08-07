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

    private const CONNECT_TIMEOUT = 30;

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

        if ($this->proxy !== null) {
            $this->logger->debug(sprintf('Download through the %s proxy', $this->proxy));
        }

        $this->copyFromUrl($url, $tmp_zip);

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
     * Filesystem::copy() opens the source with the default stream context, so neither a proxy nor any other
     * transport option can be given to it.
     *
     * @param string $url
     * @param string $target
     */
    private function copyFromUrl(string $url, string $target): void
    {
        $destination = @fopen($target, 'w');

        if ($destination === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because target file could not be opened for writing.', $url, $target), 0, null, $url);
        }

        try {
            if ($this->isCurlAvailable()) {
                $this->copyWithCurl($url, $target, $destination);
            } else {
                $this->copyWithStreamContext($url, $target, $destination);
            }
        } finally {
            if (is_resource($destination)) {
                fclose($destination);
            }
        }
    }

    /**
     * The curl extension is optional, it is only used when it is installed.
     *
     * @return bool
     */
    protected function isCurlAvailable(): bool
    {
        return extension_loaded('curl');
    }

    /**
     * @param string   $url
     * @param string   $target
     * @param resource $destination
     */
    private function copyWithCurl(string $url, string $target, $destination): void
    {
        $curl = curl_init();

        if ($curl === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because a cURL session could not be created.', $url, $target), 0, null, $url);
        }

        curl_setopt_array($curl, $this->createCurlOptions($url, $destination));

        $success = curl_exec($curl);
        $error = curl_error($curl);

        // curl_close() has no effect since PHP 8.0 and is deprecated since PHP 8.5
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        if ($success === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s": %s.', $url, $target, $error), 0, null, $url);
        }
    }

    /**
     * curl understands the proxy address as is, including the scheme and the credentials.
     *
     * @param string   $url
     * @param resource $destination
     *
     * @return array<int, mixed>
     */
    private function createCurlOptions(string $url, $destination): array
    {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $destination,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
        ];

        if ($this->proxy !== null) {
            $options[CURLOPT_PROXY] = $this->getProxyAddress();
            $options[CURLOPT_PROXYAUTH] = CURLAUTH_ANY;

            $credentials = $this->getProxyCredentials();

            if ($credentials !== null) {
                $options[CURLOPT_PROXYUSERPWD] = $credentials;
            }
        }

        return $options;
    }

    /**
     * @param string   $url
     * @param string   $target
     * @param resource $destination
     */
    private function copyWithStreamContext(string $url, string $target, $destination): void
    {
        $context = stream_context_create(['http' => $this->createStreamContextOptions()]);

        $source = @fopen($url, 'r', false, $context);

        if ($source === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $url, $target), 0, null, $url);
        }

        $copied = @stream_copy_to_stream($source, $destination);

        fclose($source);

        if ($copied === false) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s".', $url, $target), 0, null, $url);
        }
    }

    /**
     * Build the HTTP stream context options.
     *
     * PHP expects a transport in the proxy address, not an application protocol, and sends the credentials
     * in the Proxy-Authorization header, they can not be a part of the address.
     *
     * @return array<string, string|bool>
     */
    private function createStreamContextOptions(): array
    {
        if ($this->proxy === null) {
            return [];
        }

        $scheme = (string) parse_url($this->proxy, PHP_URL_SCHEME);

        if (strpos($scheme, 'socks') === 0) {
            throw new \RuntimeException(sprintf('The "%s" proxy requires the cURL extension, PHP streams support HTTP proxies only.', $this->proxy));
        }

        $options = [
            'proxy' => preg_replace('/^https?/', $scheme === 'https' ? 'ssl' : 'tcp', $this->getProxyAddress()),
            // a proxy expects the absolute URI in the request line of a plain HTTP request
            'request_fulluri' => true,
        ];

        $credentials = $this->getProxyCredentials();

        if ($credentials !== null) {
            $options['header'] = 'Proxy-Authorization: Basic '.base64_encode($credentials);
        }

        return $options;
    }

    /**
     * The address of the proxy server without the credentials.
     *
     * @return string
     */
    private function getProxyAddress(): string
    {
        $scheme = (string) parse_url((string) $this->proxy, PHP_URL_SCHEME);
        $host = (string) parse_url((string) $this->proxy, PHP_URL_HOST);
        $port = parse_url((string) $this->proxy, PHP_URL_PORT);

        return sprintf('%s://%s%s', $scheme, $host, is_int($port) ? ':'.$port : '');
    }

    /**
     * The url decoded "user:password" pair of the proxy server.
     *
     * @return string|null
     */
    private function getProxyCredentials(): ?string
    {
        $user = parse_url((string) $this->proxy, PHP_URL_USER);

        if (!is_string($user)) {
            return null;
        }

        $password = parse_url((string) $this->proxy, PHP_URL_PASS);

        return rawurldecode($user).':'.(is_string($password) ? rawurldecode($password) : '');
    }
}
