<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use GeoIp2\Database\Reader;
use GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\MaxMindDownloader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GpsLabGeoIP2Extension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $default_database = $config['default_database'];
        $default_database_config = $config['databases'][$default_database];

        // aliases for default database
        $container->setAlias('geoip2.reader', sprintf('geoip2.database.%s_reader', $default_database));
        $container->setAlias(Reader::class, sprintf('geoip2.database.%s_reader', $default_database));

        // define database services
        foreach ($config['databases'] as $name => $database) {
            $container
                ->setDefinition(sprintf('geoip2.database.%s_reader', $name), new Definition(Reader::class))
                ->setPublic(true)
                ->setLazy(true)
                ->setArguments([
                    $database['path'],
                    $database['locales'] ?: ['en']
                ]);
        }

        // define MaxMind downloader service
        $container
            ->setDefinition(MaxMindDownloader::class, new Definition(MaxMindDownloader::class))
            ->setArguments([
                new Reference('filesystem'),
                new Reference('logger')
            ]);

        $container->setAlias(Downloader::class, MaxMindDownloader::class);

        // configure update database command
        $container
            ->setDefinition(UpdateDatabaseCommand::class, new Definition(UpdateDatabaseCommand::class))
            ->setArguments([
                new Reference(Downloader::class),
                $default_database_config['url'],
                $default_database_config['path']
            ])
            ->addTag('console.command');
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $cache_dir = $container->getParameter('kernel.cache_dir');

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
