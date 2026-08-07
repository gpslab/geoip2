<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Downloader;

/**
 * Minimal HTTP proxy for test a real download through a proxy.
 *
 * Answers the first request with a "407 Proxy Authentication Required" challenge and the second one with the
 * given content, so that the credentials a client sends only after a challenge can be observed.
 *
 * The class is both the client of the proxy and the proxy itself: it runs a copy of the file in a separate
 * process, because a request has to be served while the test is blocked on the download.
 */
class ProxyServer
{
    private const ACCEPT_TIMEOUT = 10;

    /**
     * @var resource
     */
    private $process;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $requests_file;

    /**
     * @var string
     */
    private $body_file;

    /**
     * @param resource $process
     * @param string   $address
     * @param string   $requests_file
     * @param string   $body_file
     */
    private function __construct($process, string $address, string $requests_file, string $body_file)
    {
        $this->process = $process;
        $this->address = $address;
        $this->requests_file = $requests_file;
        $this->body_file = $body_file;
    }

    /**
     * @param string $body
     *
     * @return self|null null if the proxy could not be started
     */
    public static function start(string $body): ?self
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $requests_file = (string) tempnam(sys_get_temp_dir(), 'geoip2_requests_');
        $body_file = (string) tempnam(sys_get_temp_dir(), 'geoip2_body_');
        file_put_contents($body_file, $body);

        $command = sprintf(
            '%s %s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__FILE__),
            escapeshellarg($requests_file),
            escapeshellarg($body_file)
        );

        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            return null;
        }

        // the proxy prints the address it listens on as soon as it is bound
        $address = trim((string) fgets($pipes[1]));

        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($address === '') {
            proc_close($process);

            return null;
        }

        return new self($process, $address, $requests_file, $body_file);
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Wait for the proxy to finish and return everything it was asked for.
     *
     * @return string
     */
    public function stop(): string
    {
        proc_close($this->process);

        $requests = (string) file_get_contents($this->requests_file);

        unlink($this->requests_file);
        unlink($this->body_file);

        return $requests;
    }

    /**
     * Serve the requests. Runs in the separate process.
     *
     * @param string $requests_file
     * @param string $body_file
     */
    public static function run(string $requests_file, string $body_file): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if (!is_resource($server)) {
            exit(1);
        }

        echo stream_socket_get_name($server, false), PHP_EOL;
        flush();

        $body = (string) file_get_contents($body_file);
        $requests = [];

        do {
            $connection = @stream_socket_accept($server, self::ACCEPT_TIMEOUT);

            if (!is_resource($connection)) {
                break;
            }

            while (count($requests) < 2) {
                $request = self::readRequest($connection);

                if ($request === '') {
                    break;
                }

                $requests[] = $request;

                if (count($requests) === 1) {
                    // the credentials are only sent after a challenge
                    fwrite($connection, "HTTP/1.1 407 Proxy Authentication Required\r\nProxy-Authenticate: Basic realm=\"proxy\"\r\nContent-Length: 0\r\n\r\n");
                } else {
                    fwrite($connection, sprintf("HTTP/1.1 200 OK\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s", strlen($body), $body));
                }
            }

            fclose($connection);
        } while (count($requests) < 2);

        file_put_contents($requests_file, implode('', $requests));

        fclose($server);
    }

    /**
     * @param resource $connection
     *
     * @return string
     */
    private static function readRequest($connection): string
    {
        $request = '';

        while (($line = @fgets($connection)) !== false) {
            $request .= $line;

            if ($line === "\r\n") {
                break;
            }
        }

        return $request;
    }
}

// runs the proxy when the file is executed instead of being autoloaded
if (PHP_SAPI === 'cli' && isset($argv[2]) && realpath($argv[0]) === __FILE__) {
    ProxyServer::run($argv[1], $argv[2]);
}
