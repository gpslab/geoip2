<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests;

use GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\GpsLabGeoIP2Extension;
use GpsLab\Bundle\GeoIP2Bundle\GpsLabGeoIP2Bundle;
use PHPUnit\Framework\TestCase;

class GpsLabGeoIP2BundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new GpsLabGeoIP2Bundle();
        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(GpsLabGeoIP2Extension::class, $extension);
    }
}
