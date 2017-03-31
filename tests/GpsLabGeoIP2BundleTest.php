<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\PaginationBundle\Tests;

use GpsLab\Bundle\GeoIP2Bundle\GpsLabGeoIP2Bundle;

class GpsLabGeoIP2BundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new GpsLabGeoIP2Bundle();
        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(
            'GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\GpsLabGeoIP2Extension',
            $extension
        );
    }
}
