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

use GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDatabaseCommandTest extends TestCase
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

    protected function setUp(): void
    {
        $this->downloader = $this->createMock(Downloader::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->formatter = $this->createMock(OutputFormatterInterface::class);

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

    public function testNoDatabasesArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Updated databases should be a array, got null instead.');

        $command = new UpdateDatabaseCommand($this->downloader, []);
        $command->run($this->input, $this->output);
    }

    public function testInvalidDatabasesArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Updated databases should be a array, got "" instead.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn('');

        $command = new UpdateDatabaseCommand($this->downloader, []);
        $command->run($this->input, $this->output);
    }

    public function testUndefinedDatabase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined "default" database.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $command = new UpdateDatabaseCommand($this->downloader, []);
        $command->run($this->input, $this->output);
    }

    public function testUndefinedDatabase2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined "foo" database.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['foo']);

        $databases = ['default' => []];

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testNoDatabaseURL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "default" database config.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $databases = ['default' => []];

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testNoDatabasePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "default" database config.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $databases = ['default' => [
            'url' => 'https://example.com/GeoIP2.tar.gz',
        ]];

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testInvalidDatabaseURL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "default" database config.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $databases = ['default' => [
            'url' => false,
        ]];

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testInvalidDatabasePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "default" database config.');

        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $databases = ['default' => [
            'url' => 'https://example.com/GeoIP2.tar.gz',
            'path' => false,
        ]];

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testDownloadOneDatabases(): void
    {
        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['default']);

        $databases = ['default' => [
            'url' => 'https://example.com/GeoIP2.tar.gz',
            'path' => '/tmp/GeoIP2.mmdb',
        ]];

        $this->downloader
            ->expects($this->once())
            ->method('download')
            ->with($databases['default']['url'], $databases['default']['path']);

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }

    public function testDownloadSeveralDatabases(): void
    {
        $this->input
            ->expects($this->once())
            ->method('getArgument')
            ->with('databases')
            ->willReturn(['second', 'first']);

        $databases = [
            'first' => [
                'url' => 'https://example.com/GeoIP2-First.tar.gz',
                'path' => '/tmp/GeoIP2-First.mmdb',
            ],
            'second' => [
                'url' => 'https://example.com/GeoIP2-Second.tar.gz',
                'path' => '/tmp/GeoIP2-Second.mmdb',
            ],
        ];

        $this->downloader
            ->expects($this->exactly(2))
            ->method('download')
            ->withConsecutive(
                [$databases['second']['url'], $databases['second']['path']],
                [$databases['first']['url'], $databases['first']['path']]
            );

        $command = new UpdateDatabaseCommand($this->downloader, $databases);
        $command->run($this->input, $this->output);
    }
}
