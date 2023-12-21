<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use GeoIp2\Database\Reader;
use GpsLab\Bundle\GeoIP2Bundle\Command\DownloadDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\MaxMindDownloader;
use GpsLab\Bundle\GeoIP2Bundle\Reader\ReaderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GpsLabGeoIP2Extension extends Extension
{
    /**
     * Pattern of database service name.
     */
    private const SERVICE_NAME = 'geoip2.database.%s_reader';

    /**
     * @param array<array<mixed>> $configs
     * @param ContainerBuilder    $container
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $default_database = $config['default_database'];
        $databases = $config['databases'];

        // aliases for default database
        if (array_key_exists($default_database, $databases)) {
            $container->setAlias('geoip2.reader', sprintf(self::SERVICE_NAME, $default_database));
            $container->getAlias('geoip2.reader')->setPublic(true);
            $container->setAlias(Reader::class, sprintf(self::SERVICE_NAME, $default_database));
            $container->getAlias(Reader::class)->setPublic(true);
        }

        // define database services
        foreach ($databases as $name => $database) {
            $container
                ->setDefinition(sprintf(self::SERVICE_NAME, $name), new Definition(Reader::class))
                ->setPublic(true)
                ->setLazy(true)
                ->setArguments([
                    $database['path'],
                    $database['locales'],
                ]);
        }

        // define MaxMind downloader service
        $container
            ->setDefinition(MaxMindDownloader::class, new Definition(MaxMindDownloader::class))
            ->setPublic(false)
            ->setArguments([
                new Reference('filesystem'),
                new Reference('logger'),
            ]);

        $container->setAlias(Downloader::class, MaxMindDownloader::class);
        $container->getAlias(Downloader::class)->setPublic(true);

        // configure update database console command
        $container
            ->setDefinition(UpdateDatabaseCommand::class, new Definition(UpdateDatabaseCommand::class))
            ->setPublic(false)
            ->setArguments([
                new Reference(Downloader::class),
                $databases,
            ])
            ->addTag('console.command');

        // configure download database console command
        $container
            ->setDefinition(DownloadDatabaseCommand::class, new Definition(DownloadDatabaseCommand::class))
            ->setPublic(false)
            ->setArguments([
                new Reference(Downloader::class),
            ])
            ->addTag('console.command');

        // configure reader factory service
        $container
            ->setDefinition(ReaderFactory::class, new Definition(ReaderFactory::class))
            ->setPublic(false)
            ->setArguments([
                $databases,
            ]);
    }

    /**
     * @param array<array<mixed>> $config
     * @param ContainerBuilder    $container
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $cache_dir = null;

        if ($container->hasParameter('kernel.cache_dir')) {
            $cache_dir = $container->getParameter('kernel.cache_dir');
        }

        return new Configuration(is_string($cache_dir) ? $cache_dir : null);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'gpslab_geoip';
    }
}
