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
    const ROOT_NODE = 'gpslab_geoip';

    /**
     * Config tree builder.
     *
     * Example config:
     *
     * gpslab_geoip:
     *     cache: '%kernel.cache_dir%/GeoLite2-City.mmdb'
     *     url: 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz'
     *     locales: [ '%locale%' ]
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(static::ROOT_NODE);

        if (method_exists($treeBuilder, 'getRootNode')) {
            // Symfony 4.2 +
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // Symfony 4.1 and below
            $rootNode = $treeBuilder->root(static::ROOT_NODE);
        }

        return
            $rootNode
                ->children()
                    ->scalarNode('cache')
                        ->cannotBeEmpty()
                        ->defaultValue('%kernel.cache_dir%/GeoLite2-City.mmdb')
                    ->end()
                    ->scalarNode('url')
                        ->cannotBeEmpty()
                        ->defaultValue('https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz')
                    ->end()
                    ->arrayNode('locales')
                        ->treatNullLike([])
                        ->prototype('scalar')->end()
                        ->defaultValue(['%locale%'])
                    ->end()
                ->end()
            ->end()
        ;
    }
}
