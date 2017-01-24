<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class GpsLabGeoIP2Extension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (empty($config['cache'])) {
            $config['cache'] = $container->getParameter('kernel.cache_dir').'GeoLite2-Country.mmdb';
        }

        if (empty($config['url'])) {
            $config['url'] = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';
        }

        $container->setParameter('geoip2.cache', $config['cache']);
        $container->setParameter('geoip2.url', $config['url']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
