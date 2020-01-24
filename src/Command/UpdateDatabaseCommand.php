<?php
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

class UpdateDatabaseCommand extends Command
{
    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $cache;

    /**
     * @param Downloader $downloader
     * @param string $url
     * @param string $cache
     */
    public function __construct(
        Downloader $downloader,
        string $url,
        string $cache
    ) {
        $this->downloader = $downloader;
        $this->url = $url;
        $this->cache = $cache;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('geoip2:update')
            ->setDescription('Downloads and update the GeoIP2 database')
            ->addArgument(
                'url',
                InputArgument::OPTIONAL,
                'URL to downloaded GeoIP2 database',
                $this->url
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target download path',
                $this->cache
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');
        $target = $input->getArgument('target');

        $io->title('Download the GeoIP2 database');

        $this->downloader->download($url, $target);

        $io->success('Finished downloading');

        return 0;
    }
}
