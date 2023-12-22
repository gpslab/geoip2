<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Reader;

use GeoIp2\Database\Reader;
use GpsLab\Bundle\GeoIP2Bundle\Reader\ReaderFactory;
use PHPUnit\Framework\TestCase;

class ReaderFactoryTest extends TestCase
{
    /**
     * @return list<list<?list<string>>>
     */
    public function getLocales(): array
    {
        return [
            [null],
            [['en']],
            [['ru', 'en']],
        ];
    }

    /**
     * @dataProvider getLocales
     *
     * @param string[]|null $locales
     */
    public function testNoDatabases(?array $locales): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined "default" database. Available "" databases.');

        $factory = new ReaderFactory([]);
        $factory->create('default', $locales);
    }

    /**
     * @dataProvider getLocales
     *
     * @param string[]|null $locales
     */
    public function testUndefinedDatabase(?array $locales): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined "asn" database. Available "default", "city" databases.');

        $factory = new ReaderFactory([
            'default' => [],
            'city' => [],
        ]);
        $factory->create('asn', $locales);
    }

    /**
     * @dataProvider getLocales
     *
     * @param string[]|null $locales
     */
    public function testNotConfiguredDatabase(?array $locales): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database "default" is not configured.');

        $factory = new ReaderFactory([
            'default' => [],
        ]);
        $factory->create('default', $locales);
    }

    /**
     * @dataProvider getLocales
     *
     * @param string[]|null $locales
     */
    public function testEmptyPathToDatabase(?array $locales): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database "default" is not configured.');

        $factory = new ReaderFactory([
            'default' => [
                'path' => '',
            ],
        ]);
        $factory->create('default', $locales);
    }

    /**
     * @dataProvider getLocales
     *
     * @param string[]|null $locales
     */
    public function testCreate(?array $locales): void
    {
        $databases = [
            'default' => [
                'path' => tempnam(sys_get_temp_dir(), 'test_'),
            ],
        ];
        $factory = new ReaderFactory($databases, TestReader::class);

        /* @var $reader Reader|TestReader */
        $reader = $factory->create('default', $locales);

        $this->assertInstanceOf(TestReader::class, $reader);
        $this->assertSame($databases['default']['path'], $reader->filename);
        $this->assertSame($locales ?: ['en'], $reader->locales);
    }
}
