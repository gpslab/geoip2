<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\PaginationBundle\Tests\DependencyInjection;

use GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\GpsLabGeoIP2Extension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GpsLabGeoIP2ExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new GpsLabGeoIP2Extension();
        $extension->load([], $container);

        $this->assertSame('%kernel.cache_dir%/GeoLite2-City.mmdb', $container->getParameter('geoip2.cache'));
        $this->assertSame(
            'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
            $container->getParameter('geoip2.url')
        );
        $this->assertSame(['%locale%'], $container->getParameter('geoip2.locales'));
    }

    public function testGetAlias(): void
    {
        $extension = new GpsLabGeoIP2Extension();
        $this->assertSame('gpslab_geoip', $extension->getAlias());
    }
}
