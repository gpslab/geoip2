<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Command;

use GpsLab\Bundle\GeoIP2Bundle\Command\DownloadDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadDatabaseCommandTest extends TestCase
{
    /**
     * @var Downloader|MockObject
     */
    private $downloader;

    /**
     * @var InputInterface|MockObject
     */
    private $input;

    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    /**
     * @var OutputFormatterInterface|MockObject
     */
    private $formatter;

    /**
     * @var DownloadDatabaseCommand
     */
    private $command;

    protected function setUp(): void
    {
        $this->downloader = $this->createMock(Downloader::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->formatter = $this->createMock(OutputFormatterInterface::class);
        $this->command = new DownloadDatabaseCommand($this->downloader);

        $this->output
            ->method('getFormatter')
            ->willReturn($this->formatter);
        $this->formatter
            ->method('format')
            ->willReturnArgument(0);
        $this->formatter
            ->method('isDecorated')
            ->willReturn(false);
    }

    public function testNoURLArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL of downloaded GeoIP2 database should be a string, got null instead.');

        $this->command->run($this->input, $this->output);
    }

    public function testInvalidURLArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL of downloaded GeoIP2 database should be a string, got ["https:\/\/example.com\/GeoIP2.tar.gz"] instead.');

        $url = ['https://example.com/GeoIP2.tar.gz'];
        $target = '/tmp/GeoIP2.mmdb';

        $this->input
            ->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(['url'], ['target'])
            ->willReturnOnConsecutiveCalls($url, $target);

        $this->command->run($this->input, $this->output);
    }

    public function testNoTargetArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target download path should be a string, got null instead.');

        $url = 'https://example.com/GeoIP2.tar.gz';
        $target = null;

        $this->input
            ->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(['url'], ['target'])
            ->willReturnOnConsecutiveCalls($url, $target);

        $this->command->run($this->input, $this->output);
    }

    public function testInvalidTargetArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target download path should be a string, got ["\/tmp\/GeoIP2.mmdb"] instead.');

        $url = 'https://example.com/GeoIP2.tar.gz';
        $target = ['/tmp/GeoIP2.mmdb'];

        $this->input
            ->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(['url'], ['target'])
            ->willReturnOnConsecutiveCalls($url, $target);

        $this->command->run($this->input, $this->output);
    }

    public function testDownload(): void
    {
        $url = 'https://example.com/GeoIP2.tar.gz';
        $target = '/tmp/GeoIP2.mmdb';

        $this->input
            ->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(['url'], ['target'])
            ->willReturnOnConsecutiveCalls($url, $target);

        $this->downloader
            ->expects($this->once())
            ->method('download')
            ->with($url, $target);

        $this->command->run($this->input, $this->output);
    }
}
