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

    /**
     * @var MaxMindDownloader
     */
    private $downloader;

    protected function setUp(): void
    {
        $this->fs = $this->createMock(Filesystem::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->downloader = new MaxMindDownloader($this->fs, $this->logger);
    }

    public function testNotFoundDatabase(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found GeoLite2 database in archive.');

        $url = 'https://example.com';
        $target = sprintf('%s/%s_GeoLite2.mmdb', sys_get_temp_dir(), uniqid('', true));

        $tmp_zip_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar\.gz$#', sys_get_temp_dir());
        $tmp_unzip_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar$#', sys_get_temp_dir());
        $tmp_untar_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2$#', sys_get_temp_dir());

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        $fs_call = 0;
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('remove')
            ->willReturnCallback(function ($files) use ($tmp_zip_regexp, $tmp_unzip_regexp, $tmp_untar_regexp) {
                $this->assertIsArray($files);
                $this->assertCount(3, $files);
                $this->assertArrayHasKey(0, $files);
                $this->assertArrayHasKey(1, $files);
                $this->assertArrayHasKey(2, $files);
                $this->assertIsString($files[0]);
                $this->assertIsString($files[1]);
                $this->assertIsString($files[2]);
                $this->assertRegExp($tmp_zip_regexp, $files[0]);
                $this->assertRegExp($tmp_unzip_regexp, $files[1]);
                $this->assertRegExp($tmp_untar_regexp, $files[2]);
            });
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('copy')
            ->willReturnCallback(function ($origin_file, $target_file, $overwrite_newer_files) use (
                $url,
                $tmp_zip_regexp
            ) {
                $this->assertSame($url, $origin_file);
                $this->assertIsString($target_file);
                $this->assertTrue($overwrite_newer_files);
                $this->assertRegExp($tmp_zip_regexp, $target_file);

                // make test GeoLite2 db
                file_put_contents($target_file, base64_decode(self::TAR_GZ_BAD));
            });
        $this->fs
            ->expects($this->at($fs_call))
            ->method('mkdir')
            ->with(dirname($target), 0755);

        $this->downloader->download($url, $target);
    }

    public function testDownload(): void
    {
        $url = 'https://example.com';
        $target = sprintf('%s/%s_GeoLite2.mmdb', sys_get_temp_dir(), uniqid('', true));

        $tmp_zip_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar\.gz$#', sys_get_temp_dir());
        $tmp_unzip_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2\.tar$#', sys_get_temp_dir());
        $tmp_untar_regexp = sprintf('#^%s/[\da-f]+\.\d+_GeoLite2$#', sys_get_temp_dir());

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug');

        $fs_call = 0;
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('remove')
            ->willReturnCallback(function ($files) use ($tmp_zip_regexp, $tmp_unzip_regexp, $tmp_untar_regexp) {
                $this->assertIsArray($files);
                $this->assertCount(3, $files);
                $this->assertArrayHasKey(0, $files);
                $this->assertArrayHasKey(1, $files);
                $this->assertArrayHasKey(2, $files);
                $this->assertIsString($files[0]);
                $this->assertIsString($files[1]);
                $this->assertIsString($files[2]);
                $this->assertRegExp($tmp_zip_regexp, $files[0]);
                $this->assertRegExp($tmp_unzip_regexp, $files[1]);
                $this->assertRegExp($tmp_untar_regexp, $files[2]);
            });
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('copy')
            ->willReturnCallback(function ($origin_file, $target_file, $overwrite_newer_files) use (
                $url,
                $tmp_zip_regexp
            ) {
                $this->assertSame($url, $origin_file);
                $this->assertIsString($target_file);
                $this->assertTrue($overwrite_newer_files);
                $this->assertRegExp($tmp_zip_regexp, $target_file);

                // make test GeoLite2 db
                file_put_contents($target_file, base64_decode(self::TAR_GZ));
            });
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('mkdir')
            ->with(dirname($target), 0755);
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('copy')
            ->willReturnCallback(function ($origin_file, $target_file, $overwrite_newer_files) use ($target) {
                $this->assertIsString($origin_file);
                $this->assertSame($target, $target_file);
                $this->assertTrue($overwrite_newer_files);
                $regexp = sprintf(
                    '#^%s/[\da-f]+\.\d+_GeoLite2/GeoLite2-City_20200114/GeoLite2.mmdb$#',
                    sys_get_temp_dir()
                );
                $this->assertRegExp($regexp, $origin_file);
                $this->assertFileExists($origin_file);
                $this->assertSame('TestGeoLite2', file_get_contents($origin_file));
            });
        $this->fs
            ->expects($this->at($fs_call++))
            ->method('chmod')
            ->with($target, 0755);
        $this->fs
            ->expects($this->at($fs_call))
            ->method('remove')
            ->willReturnCallback(function ($files) use ($tmp_zip_regexp, $tmp_unzip_regexp, $tmp_untar_regexp) {
                $this->assertIsArray($files);
                $this->assertCount(3, $files);
                $this->assertArrayHasKey(0, $files);
                $this->assertArrayHasKey(1, $files);
                $this->assertArrayHasKey(2, $files);
                $this->assertIsString($files[0]);
                $this->assertIsString($files[1]);
                $this->assertIsString($files[2]);
                $this->assertRegExp($tmp_zip_regexp, $files[0]);
                $this->assertRegExp($tmp_unzip_regexp, $files[1]);
                $this->assertRegExp($tmp_untar_regexp, $files[2]);
            });

        $this->downloader->download($url, $target);
    }
}
