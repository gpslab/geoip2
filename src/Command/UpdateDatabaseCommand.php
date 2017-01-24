<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Command;

use GpsLab\Component\Compressor\CompressorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

class UpdateDatabaseCommand extends Command
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var CompressorInterface
     */
    private $compressor;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $cache = '';

    /**
     * @param Filesystem $fs
     * @param Stopwatch $stopwatch
     * @param CompressorInterface $compressor
     * @param string $url
     * @param string $cache
     */
    public function __construct(Filesystem $fs, Stopwatch $stopwatch, CompressorInterface $compressor, $url, $cache)
    {
        $this->fs = $fs;
        $this->url = $url;
        $this->cache = $cache;
        $this->stopwatch = $stopwatch;
        $this->compressor = $compressor;

        parent::__construct();
    }

    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $target = $input->getArgument('target');

        $io = new SymfonyStyle($input, $output);

        $io->title('Update the GeoIP2 database');
        $this->stopwatch->start('update');

        $tmp = sys_get_temp_dir().basename($target);

        $io->comment(sprintf('Beginning download of file: %s', $url));

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_FILE => fopen($tmp, 'wb'),
            CURLOPT_TIMEOUT => 28800,
            CURLOPT_URL => $url
        ]);
        curl_exec($ch);

        $io->comment('Download complete');
        $io->comment('De-compressing file');

        $this->compressor->uncompress($tmp, $target);
        $this->fs->chmod($target, 0777);

        $io->comment('Decompression complete');
        $io->success('Finished downloading.');

        $event = $this->stopwatch->stop('update');
        $io->writeln([
            sprintf('Time: <info>%.2F</info> s.', $event->getDuration() / 1000),
            sprintf('Memory: <info>%.2F</info> MiB.', $event->getMemory() / 1024 / 1024),
        ]);

        return 0;
    }
}
