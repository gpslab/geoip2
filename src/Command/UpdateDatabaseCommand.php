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
use Symfony\Component\Stopwatch\StopwatchEvent;

class UpdateDatabaseCommand extends Command
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $cache;

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
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');
        $target = $input->getArgument('target');

        $io->title('Update the GeoIP2 database');
        $this->stopwatch->start('update');

        $tmp_zip = sys_get_temp_dir().'/GeoLite2.tar.gz';
        $tmp_unzip = sys_get_temp_dir().'/GeoLite2.tar';
        $tmp_untar = sys_get_temp_dir().'/GeoLite2';

        // remove old files and folders for correct overwrite it
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $io->comment(sprintf('Beginning download of file <info>%s</info>', $url));

        file_put_contents($tmp_zip, fopen($url, 'rb'));

        $io->comment(sprintf('Download complete to <info>%s</info>', $tmp_zip));
        $io->comment(sprintf('De-compressing file to <info>%s</info>', $tmp_unzip));

        $this->fs->mkdir(dirname($target), 0777);

        // decompress gz file
        $phar = new \PharData($tmp_zip);
        $phar->decompress();

        $io->comment('Decompression complete');
        $io->comment(sprintf('Extract tar file to <info>%s</info>', $tmp_untar));

        // extract tar archive
        $phar = new \PharData($tmp_unzip);
        $phar->extractTo($tmp_untar);

        $io->comment('Tar archive extracted');

        // find database in archive
        $database = '';
        foreach (scandir($tmp_untar) as $folder) {
            $path = $tmp_untar.'/'.$folder;

            // find folder with database
            // expected something like that "GeoLite2-City_20200114"
            if (
                preg_match('/^(?<database>.+)_(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})$/', $folder, $match) &&
                is_dir($path)
            ) {
                // find database in folder
                // expected something like that "GeoLite2-City.mmdb"
                foreach (scandir($path) as $filename) {
                    $file = $path.'/'.$filename;

                    if (strpos($filename, $match['database']) === 0 && is_file($file)) {
                        $io->comment(sprintf(
                            'Found <info>%s</info> database updated at <info>%s-%s-%s</info>',
                            $match['database'],
                            $match['year'],
                            $match['month'],
                            $match['day']
                        ));

                        $database = $file;
                    }
                }
            }
        }

        if (!$database) {
            throw new \RuntimeException('Not found GeoLite2 database in archive.');
        }

        $this->fs->copy($database, $target, true);
        $this->fs->chmod($target, 0777);
        $this->fs->remove([$tmp_zip, $tmp_unzip, $tmp_untar]);

        $io->comment(sprintf('Database moved to <info>%s</info>', $target));

        $io->success('Finished downloading');

        $this->stopwatch($io, $this->stopwatch->stop('update'));

        return 0;
    }

    /**
     * @param SymfonyStyle $io
     * @param StopwatchEvent $event
     */
    private function stopwatch(SymfonyStyle $io, StopwatchEvent $event)
    {
        $io->writeln([
            sprintf('Time: <info>%.2F</info> s.', $event->getDuration() / 1000),
            sprintf('Memory: <info>%.2F</info> MiB.', $event->getMemory() / 1024 / 1024),
        ]);
    }
}
