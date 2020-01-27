<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Command;

use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadDatabaseCommand extends Command
{
    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @param Downloader $downloader
     */
    public function __construct(Downloader $downloader)
    {
        $this->downloader = $downloader;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('geoip2:download')
            ->setDescription('Downloads the GeoIP2 database')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'URL of downloaded GeoIP2 database'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Target download path'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');
        $target = $input->getArgument('target');

        if (!is_string($url)) {
            throw new \InvalidArgumentException(sprintf('URL of downloaded GeoIP2 database should be a string, got %s instead.', json_encode($url)));
        }

        if (!is_string($target)) {
            throw new \InvalidArgumentException(sprintf('Target download path should be a string, got %s instead.', json_encode($target)));
        }

        $io->title('Download the GeoIP2 database');

        $this->downloader->download($url, $target);

        $io->success('Finished downloading');

        return 0;
    }
}
