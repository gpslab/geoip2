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

class UpdateDatabaseCommand extends Command
{
    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var array[]
     */
    private $databases;

    /**
     * @param Downloader $downloader
     * @param array[]    $databases
     */
    public function __construct(Downloader $downloader, array $databases)
    {
        $this->downloader = $downloader;
        $this->databases = $databases;
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<'EOT'
The <info>%command.name%</info> command update all configured databases:

    <info>%command.full_name%</info>

EOT;

        if (count($this->databases) >= 2) {
            $databases_help = '';
            foreach (array_keys($this->databases) as $i => $name) {
                $databases_help .= sprintf(' * <info>%s</info>'.PHP_EOL, $name);
            }
            [$first, $second, ] = array_keys($this->databases);

            $help .= <<<'EOT'

Update the <info>$first</info> and <info>$second</info> database:

    <info>%command.full_name% $first $second</info>

List of available databases:

$databases_help
EOT;
        }

        $this
            ->setName('geoip2:update')
            ->setDescription('Update the GeoIP2 databases')
            ->addArgument(
                'databases',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Updated databases',
                array_keys($this->databases)
            )
            ->setHelp($help);
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
        $databases = $input->getArgument('databases');

        $io->title('Update the GeoIP2 databases');

        if (!is_array($databases)) {
            throw new \InvalidArgumentException(sprintf('Updated databases should be a array, got %s instead.', json_encode($databases)));
        }

        foreach ($databases as $database) {
            if (!array_key_exists($database, $this->databases)) {
                throw new \InvalidArgumentException(sprintf('Undefined "%s" database.', $database));
            }

            if (!array_key_exists('url', $this->databases[$database]) ||
                !array_key_exists('path', $this->databases[$database]) ||
                !is_string($this->databases[$database]['url']) ||
                !is_string($this->databases[$database]['path'])
            ) {
                throw new \InvalidArgumentException(sprintf('Invalid "%s" database config.', $database));
            }

            $io->section(sprintf('Update "%s" database', $database));

            $this->downloader->download($this->databases[$database]['url'], $this->databases[$database]['path']);

            $io->comment(sprintf('Database <info>%s</info> updated', $database));
        }

        $io->success('Finished updating');

        return 0;
    }
}
