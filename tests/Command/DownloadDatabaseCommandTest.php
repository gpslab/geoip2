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

    public function testNoURLExecute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL of downloaded GeoIP2 database should be a string, got null instead.');

        $this->command->run($this->input, $this->output);
    }

    public function testNoTargetExecute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target download path should be a string, got null instead.');

        $this->input
            ->expects($this->at(4))
            ->method('getArgument')
            ->with('url')
            ->willReturn('https://example.com/GeoIP2.tar.gz');

        $this->command->run($this->input, $this->output);
    }

    public function testExecute(): void
    {
        $url = 'https://example.com/GeoIP2.tar.gz';
        $target = '/tmp/GeoIP2.mmdb';

        $this->input
            ->expects($this->at(4))
            ->method('getArgument')
            ->with('url')
            ->willReturn($url);
        $this->input
            ->expects($this->at(5))
            ->method('getArgument')
            ->with('target')
            ->willReturn($target);

        $this->downloader
            ->expects($this->once())
            ->method('download')
            ->with($url, $target);

        $this->command->run($this->input, $this->output);
    }
}
