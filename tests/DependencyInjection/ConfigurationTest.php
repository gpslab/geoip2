<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\DependencyInjection;

use GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private const URL = 'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz';
    private const PATH = '%s/%s.mmdb';

    /**
     * @return array[]
     */
    public function getBadConfigs(): array
    {
        $return = [];
        foreach (['/tmp/var/cache', null] as $cache_dir) {
            $return[] = [$cache_dir, []];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => null,
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => null,
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [],
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'EDITION',
                    'databases' => [],
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'default_database' => 'default',
                    'databases' => [
                        'foo' => [
                            'license' => 'LICENSE',
                            'edition' => 'EDITION',
                        ],
                    ],
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'default_database' => '',
                    'databases' => [
                        'foo' => [
                            'license' => 'LICENSE',
                            'edition' => 'EDITION',
                        ],
                    ],
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'EDITION',
                    'locales' => [],
                ],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [
                        'default' => [
                            'license' => 'LICENSE',
                            'edition' => 'EDITION',
                            'locales' => [],
                        ],
                    ],
                ],
            ]];
        }

        return $return;
    }

    /**
     * @dataProvider getBadConfigs
     *
     * @param string|null $cache_dir
     * @param array[]     $configs
     */
    public function testBadConfigs(?string $cache_dir, array $configs): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $configuration = new Configuration($cache_dir);
        $tree_builder = $configuration->getConfigTreeBuilder();

        $processor = new Processor();
        $processor->process($tree_builder->buildTree(), $configs);
    }

    /**
     * @return array[]
     */
    public function getConfigs(): array
    {
        $return = [];
        foreach (['/tmp/var/cache', null] as $cache_dir) {
            $real_cache_dir = $cache_dir ?: sys_get_temp_dir();

            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'EDITION',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'license' => 'LICENSE',
                        'edition' => 'EDITION',
                        'url' => sprintf(self::URL, 'EDITION', 'LICENSE'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION'),
                        'locales' => ['en'],
                    ],
                ],
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'databases' => [
                        'default' => [
                            'edition' => 'EDITION',
                            'locales' => ['ru'],
                        ],
                    ],
                ],
            ], [
                'license' => 'LICENSE',
                'databases' => [
                    'default' => [
                        'edition' => 'EDITION',
                        'locales' => ['ru'],
                        'license' => 'LICENSE',
                        'url' => sprintf(self::URL, 'EDITION', 'LICENSE'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION'),
                    ],
                ],
                'default_database' => 'default',
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'default_database' => 'foo',
                    'databases' => [
                        'foo' => [
                            'license' => 'LICENSE1',
                            'edition' => 'EDITION_1',
                        ],
                        'bar' => [
                            'license' => 'LICENSE_2',
                            'edition' => 'EDITION_2',
                            'locales' => ['ru'],
                        ],
                        'baz' => [
                            'license' => 'LICENSE_3',
                            'edition' => 'EDITION_3',
                            'locales' => ['fr', 'en'],
                        ],
                    ],
                ],
            ], [
                'default_database' => 'foo',
                'databases' => [
                    'foo' => [
                        'license' => 'LICENSE1',
                        'edition' => 'EDITION_1',
                        'url' => sprintf(self::URL, 'EDITION_1', 'LICENSE1'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION_1'),
                        'locales' => ['en'],
                    ],
                    'bar' => [
                        'license' => 'LICENSE_2',
                        'edition' => 'EDITION_2',
                        'locales' => ['ru'],
                        'url' => sprintf(self::URL, 'EDITION_2', 'LICENSE_2'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION_2'),
                    ],
                    'baz' => [
                        'license' => 'LICENSE_3',
                        'edition' => 'EDITION_3',
                        'locales' => ['fr', 'en'],
                        'url' => sprintf(self::URL, 'EDITION_3', 'LICENSE_3'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION_3'),
                    ],
                ],
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE_1',
                    'databases' => [
                        'default' => [
                            'edition' => 'EDITION_1',
                            'locales' => ['ru'],
                        ],
                        'foo' => [
                            'edition' => 'EDITION_2',
                            'license' => 'LICENSE_2',
                        ],
                    ],
                ],
            ], [
                'license' => 'LICENSE_1',
                'databases' => [
                    'default' => [
                        'edition' => 'EDITION_1',
                        'locales' => ['ru'],
                        'license' => 'LICENSE_1',
                        'url' => sprintf(self::URL, 'EDITION_1', 'LICENSE_1'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION_1'),
                    ],
                    'foo' => [
                        'edition' => 'EDITION_2',
                        'license' => 'LICENSE_2',
                        'url' => sprintf(self::URL, 'EDITION_2', 'LICENSE_2'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION_2'),
                        'locales' => ['en'],
                    ],
                ],
                'default_database' => 'default',
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'url' => 'https://example.com/GoeIp2.tar.gz',
                    'path' => '/var/local/GoeIp2',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'url' => 'https://example.com/GoeIp2.tar.gz',
                        'path' => '/var/local/GoeIp2',
                        'locales' => ['en'],
                    ],
                ],
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'url' => 'https://example.com/GoeIp2.tar.gz',
                    'edition' => 'EDITION',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'url' => 'https://example.com/GoeIp2.tar.gz',
                        'edition' => 'EDITION',
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION'),
                        'locales' => ['en'],
                    ],
                ],
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [
                        'default' => [
                            'url' => 'https://example.com/GoeIp2.tar.gz',
                            'path' => '/var/local/GoeIp2',
                        ],
                        'foo' => [
                            'url' => 'https://example.com/GoeIp2.tar.gz',
                            'edition' => 'EDITION',
                        ],
                    ],
                ],
            ], [
                'databases' => [
                    'default' => [
                        'url' => 'https://example.com/GoeIp2.tar.gz',
                        'path' => '/var/local/GoeIp2',
                        'locales' => ['en'],
                    ],
                    'foo' => [
                        'url' => 'https://example.com/GoeIp2.tar.gz',
                        'edition' => 'EDITION',
                        'path' => sprintf(self::PATH, $real_cache_dir, 'EDITION'),
                        'locales' => ['en'],
                    ],
                ],
                'default_database' => 'default',
                'locales' => ['en'],
            ]];
        }

        return $return;
    }

    /**
     * @dataProvider getConfigs
     *
     * @param string|null $cache_dir
     * @param array[]     $configs
     * @param array[]     $expected
     */
    public function testConfigs(?string $cache_dir, array $configs, array $expected): void
    {
        $configuration = new Configuration($cache_dir);
        $tree_builder = $configuration->getConfigTreeBuilder();

        $processor = new Processor();
        $result = $processor->process($tree_builder->buildTree(), $configs);
        $this->assertSame($expected, $result);
    }
}
