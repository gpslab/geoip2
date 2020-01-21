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
    private const ROOT_NODE = 'gpslab_geoip';

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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree_builder = new TreeBuilder(self::ROOT_NODE);

        if (method_exists($tree_builder, 'getRootNode')) {
            // Symfony 4.2 +
            $root_node = $tree_builder->getRootNode();
        } else {
            // Symfony 4.1 and below
            $root_node = $tree_builder->root(self::ROOT_NODE);
        }

        $cache = $root_node->children()->scalarNode('cache');
        $cache
            ->cannotBeEmpty()
            ->defaultValue('%kernel.cache_dir%/GeoLite2-City.mmdb')
        ;

        $url = $root_node->children()->scalarNode('url');
        $url
            ->cannotBeEmpty()
            ->defaultValue('https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz')
        ;

        $locales = $root_node->children()->arrayNode('locales');
        $locales->prototype('scalar');
        $locales
            ->treatNullLike([])
            ->defaultValue(['%locale%'])
        ;

        return $tree_builder;
    }
}
