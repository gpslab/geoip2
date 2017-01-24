<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Config tree builder.
     *
     * Example config:
     *
     * gpslab_geoip:
     *     cache: '%kernel.cache_dir%GeoLite2-Country.mmdb'
     *     url: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz'
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        return (new TreeBuilder())
            ->root('gps_lab_geoip2')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('cache')->end()
                ->scalarNode('url')->end()
            ->end();
    }
}
