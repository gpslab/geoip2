<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\PaginationBundle\Tests\DependencyInjection;

use GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\GpsLabGeoIP2Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GpsLabGeoIP2ExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $extension = new GpsLabGeoIP2Extension();
        $extension->load([], $container);

        $this->assertEquals('%kernel.cache_dir%/GeoLite2-City.mmdb', $container->getParameter('geoip2.cache'));
        $this->assertEquals(
            'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
            $container->getParameter('geoip2.url')
        );
        $this->assertEquals(['%locale%'], $container->getParameter('geoip2.locales'));
    }

    public function testGetAlias()
    {
        $extension = new GpsLabGeoIP2Extension();
        $this->assertEquals('gpslab_geoip', $extension->getAlias());
    }
}
