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
        /* @var $container \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container
            ->expects($this->at(0))
            ->method('setParameter')
            ->with('geoip2.cache', '%kernel.cache_dir%/GeoLite2-City.mmdb')
        ;
        $container
            ->expects($this->at(1))
            ->method('setParameter')
            ->with('geoip2.url', 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz')
        ;
        $container
            ->expects($this->at(2))
            ->method('setParameter')
            ->with('geoip2.locales', ['%locale%'])
        ;

        $extension = new GpsLabGeoIP2Extension();
        $extension->load([], $container);
    }

    public function testGetAlias()
    {
        $extension = new GpsLabGeoIP2Extension();
        $this->assertEquals('gpslab_geoip', $extension->getAlias());
    }
}
