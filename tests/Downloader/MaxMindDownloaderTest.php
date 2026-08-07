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

use GpsLab\Bundle\GeoIP2Bundle\Downloader\MaxMindDownloader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class MaxMindDownloaderTest extends TestCase
{
    /**
     * Gzipped tar archive with path "GeoLite2-City_20200114/GeoLite2.mmdb" and content "TestGeoLite2".
     */
    private const TAR_GZ = 'H4sIAAAAAAAAA3NPzffJLEk10nXOLKmMNzIwMjAwNDTRd4cK6+XmpiQxUAgMgMDMwACrOBgYmjAYGpsZGpsamRoamwDFDY2MzM0UMHXQAJQWlyQWMWBx3cgAIanFJbDIHmi3jIJRMApGwSigHwAAvq9e6AAIAAA=';

    /**
     * Gzipped tar archive with path "GeoLite2.mmdb".
     */
    private const TAR_GZ_BAD = 'H4sIAAAAAAAAA3NPzffJLEk10svNTUlioA0wAAIzAwOs4lDAYGhsZmhsamZoZm4GEjc3MDRWwNRBA1BaXJJYxIDFdaNgFIyCUTC8AQCdCzBEAAYAAA==';

    /**
     * @var Filesystem|MockObject
     */
    private $fs;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->fs = $this->createMock(Filesystem::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        ProxyStreamWrapper::unregister();
    }

    public function testNotFoundDatabase(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found GeoLite2 database in archive.');

        $target = $this->createTarget();

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        $this->fs
            ->expects($this->once())
            ->method('remove')
            ->willReturnCallback(function ($files): void {
                $this->assertTemporaryFiles($files);
            });
        $this->fs
            ->expects($this->never())
            ->method('copy');
        $this->fs
            ->expects($this->once())
            ->method('mkdir')
            ->with(dirname($target), 0755);

        ProxyStreamWrapper::register(base64_decode(self::TAR_GZ_BAD));

        $downloader = new StreamMaxMindDownloader($this->fs, $this->logger);
        $downloader->download($this->createUrl(), $target);
    }

    public function testDownload(): void
    {
        $this->assertDownloadWithPermissions(new StreamMaxMindDownloader($this->fs, $this->logger), 0755);
    }

    public function testDownloadWithPermissions(): void
    {
        $this->assertDownloadWithPermissions(new StreamMaxMindDownloader($this->fs, $this->logger, 0644), 0644);
    }

    /**
     * @return array<array{string, array<string, string|bool>}>
     */
    public static function getProxies(): array
    {
        return [
            // an application protocol is replaced with the transport PHP expects
            ['http://proxy.example.com:3128', [
                'proxy' => 'tcp://proxy.example.com:3128',
                'request_fulluri' => true,
            ]],
            ['https://proxy.example.com:3129', [
                'proxy' => 'ssl://proxy.example.com:3129',
                'request_fulluri' => true,
            ]],
            // the port is optional
            ['http://proxy.example.com', [
                'proxy' => 'tcp://proxy.example.com',
                'request_fulluri' => true,
            ]],
            // credentials are moved from the address to the Proxy-Authorization header and are url decoded
            ['http://user:p%40ss@proxy.example.com:3128', [
                'proxy' => 'tcp://proxy.example.com:3128',
                'request_fulluri' => true,
                'header' => 'Proxy-Authorization: Basic dXNlcjpwQHNz', // user:p@ss
            ]],
            ['http://user@proxy.example.com:3128', [
                'proxy' => 'tcp://proxy.example.com:3128',
                'request_fulluri' => true,
                'header' => 'Proxy-Authorization: Basic dXNlcjo=', // user:
            ]],
        ];
    }

    /**
     * @dataProvider getProxies
     *
     * @param string                     $proxy
     * @param array<string, string|bool> $expected_options
     */
    #[DataProvider('getProxies')]
    public function testDownloadThroughProxyWithStreams(string $proxy, array $expected_options): void
    {
        $target = $this->createTarget();

        $this->expectSuccessfulDownload($target, 0755);

        ProxyStreamWrapper::register(base64_decode(self::TAR_GZ));

        $downloader = new StreamMaxMindDownloader($this->fs, $this->logger, 0755, $proxy);
        $downloader->download($this->createUrl(), $target);

        $this->assertSame(['http' => $expected_options], ProxyStreamWrapper::$context_options);
    }

    public function testStreamsDoNotSupportSocksProxy(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "socks5://proxy.example.com:1080" proxy requires the cURL extension, PHP streams support HTTP proxies only.');

        $downloader = new StreamMaxMindDownloader($this->fs, $this->logger, 0755, 'socks5://proxy.example.com:1080');
        $downloader->download($this->createUrl(), $this->createTarget());
    }

    /**
     * Downloads through a real proxy running in a separate process.
     */
    public function testDownloadThroughProxyWithCurl(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The cURL extension is not installed.');
        }

        $proxy = ProxyServer::start(base64_decode(self::TAR_GZ));

        if ($proxy === null) {
            $this->markTestSkipped('The proxy server could not be started.');
        }

        $target = $this->createTarget();

        $this->expectSuccessfulDownload($target, 0755);

        $downloader = new MaxMindDownloader(
            $this->fs,
            $this->logger,
            0755,
            sprintf('http://user:p%%40ss@%s', $proxy->getAddress())
        );
        $downloader->download('http://example.com/GeoLite2-City.tar.gz', $target);

        $requests = $proxy->stop();

        // the absolute URI in the request line is what tells the proxy where to go
        $this->assertPatternMatches('#^GET http://example\.com/GeoLite2-City\.tar\.gz #', $requests);
        // the credentials are only sent after the 407 challenge
        $this->assertNotFalse(strpos($requests, 'Proxy-Authorization: Basic dXNlcjpwQHNz')); // user:p@ss
    }

    /**
     * @param MaxMindDownloader $downloader
     * @param int               $permissions
     */
    private function assertDownloadWithPermissions(MaxMindDownloader $downloader, int $permissions): void
    {
        $target = $this->createTarget();

        $this->expectSuccessfulDownload($target, $permissions);

        ProxyStreamWrapper::register(base64_decode(self::TAR_GZ));

        $downloader->download($this->createUrl(), $target);

        // no proxy is configured, the stream context stays empty
        $this->assertSame([], ProxyStreamWrapper::$context_options);
    }

    /**
     * The archive is fetched by the downloader itself, the filesystem only moves the extracted database.
     *
     * @param string $target
     * @param int    $permissions
     */
    private function expectSuccessfulDownload(string $target, int $permissions): void
    {
        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        $this->fs
            ->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function ($files): void {
                $this->assertTemporaryFiles($files);
            });
        $this->fs
            ->expects($this->once())
            ->method('mkdir')
            ->with(dirname($target), 0755);
        $this->fs
            ->expects($this->once())
            ->method('copy')
            ->willReturnCallback(function ($origin_file, $target_file, $overwrite_newer_files) use ($target): void {
                $this->assertSame($target, $target_file);
                $this->assertTrue($overwrite_newer_files);
                $this->assertIsString($origin_file);
                $path_quote = preg_quote(sys_get_temp_dir(), '#');
                $regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2/GeoLite2-City_20200114/GeoLite2.mmdb$#', $path_quote);
                self::assertPatternMatches($regexp, $origin_file);
                $this->assertSame('TestGeoLite2', file_get_contents($origin_file));
            });
        $this->fs
            ->expects($this->once())
            ->method('chmod')
            ->with($target, $permissions);
    }

    /**
     * @param mixed $files
     */
    private function assertTemporaryFiles($files): void
    {
        $path_quote = preg_quote(sys_get_temp_dir(), '#');

        $this->assertIsArray($files);
        $this->assertCount(3, $files);
        $this->assertArrayHasKey(0, $files);
        $this->assertArrayHasKey(1, $files);
        $this->assertArrayHasKey(2, $files);
        $this->assertIsString($files[0]);
        $this->assertIsString($files[1]);
        $this->assertIsString($files[2]);
        self::assertPatternMatches(sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar\.gz$#', $path_quote), $files[0]);
        self::assertPatternMatches(sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar$#', $path_quote), $files[1]);
        self::assertPatternMatches(sprintf('#^%s/[\da-f]+\.\d+_GeoLite2$#', $path_quote), $files[2]);
    }

    /**
     * @return string
     */
    private function createTarget(): string
    {
        return sprintf('%s/%s_GeoLite2.mmdb', sys_get_temp_dir(), uniqid('', true));
    }

    /**
     * @return string
     */
    private function createUrl(): string
    {
        return sprintf('%s://example.com/GeoLite2-City.tar.gz', ProxyStreamWrapper::PROTOCOL);
    }

    /**
     * Hook for BC between PHPUnit versions.
     *
     * PHPUnit 8 and below know assertRegExp() only, it is removed in PHPUnit 10.
     * PHPUnit 9.1 + know assertMatchesRegularExpression(), it is final since PHPUnit 10 and can not be shimmed.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    private static function assertPatternMatches(string $pattern, string $string, string $message = ''): void
    {
        $message = $message ?: sprintf('Failed asserting that "%s" matches PCRE pattern "%s".', $string, $pattern);

        self::assertSame(1, preg_match($pattern, $string), $message);
    }
}
