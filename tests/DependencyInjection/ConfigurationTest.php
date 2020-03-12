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
        $configurations = [];
        // undefined edition
        $configurations[] = [
            'gpslab_geoip' => [
                'license' => 'LICENSE',
            ],
        ];
        // permissible values: "GeoLite2-ASN", "GeoLite2-City", "GeoLite2-Country"
        $configurations[] = [
            'gpslab_geoip' => [
                'license' => 'LICENSE',
                'edition' => 'EDITION',
            ],
        ];
        // unrecognized option "edition" under "gpslab_geoip"
        $configurations[] = [
            'gpslab_geoip' => [
                'license' => 'LICENSE',
                'edition' => 'GeoLite2-City',
                'databases' => [],
            ],
        ];
        // undefined default database
        $configurations[] = [
            'gpslab_geoip' => [
                'default_database' => 'default',
                'databases' => [
                    'foo' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                    ],
                ],
            ],
        ];
        // undefined default database
        $configurations[] = [
            'gpslab_geoip' => [
                'default_database' => '',
                'databases' => [
                    'foo' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                    ],
                ],
            ],
        ];
        // invalid URL
        $configurations[] = [
            'gpslab_geoip' => [
                'databases' => [
                    'default' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                        'url' => 'example.com',
                        'path' => '/tmp/GeoIP2-First.mmdb',
                    ],
                ],
            ],
        ];

        $full_config = [
            'license' => 'LICENSE',
            'edition' => 'GeoLite2-City',
            'url' => 'https://example.com/GoeIp2.tar.gz',
            'path' => '/var/local/GoeIp2',
            'locales' => ['en'],
        ];

        foreach (['license', 'edition', 'url', 'path', 'locales'] as $option) {
            $config = $full_config;
            $config[$option] = ''; // reset option to empty string

            // empty option in root
            $configurations[] = [
                'gpslab_geoip' => $config,
            ];

            // empty option in database
            $configurations[] = [
                'gpslab_geoip' => [
                    'databases' => [
                        'default' => $config,
                    ],
                ],
            ];
        }

        foreach (['license', 'edition'] as $option) {
            $config = $full_config;
            unset($config[$option]); // remove required option

            // undefined option in root
            $configurations[] = [
                'gpslab_geoip' => $config,
            ];

            // undefined option in database
            $configurations[] = [
                'gpslab_geoip' => [
                    'databases' => [
                        'default' => $config,
                    ],
                ],
            ];
        }

        $return = [];
        foreach ($configurations as $configuration) {
            foreach (['/tmp/var/cache', null] as $cache_dir) {
                $return[] = [$cache_dir, $configuration];
            }
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

            $return[] = [$cache_dir, [], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => null,
            ], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [],
            ], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => null,
                ],
            ], [
                'databases' => [],
                'locales' => ['en'],
                'default_database' => 'default',
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [],
                ],
            ], [
                'databases' => [],
                'locales' => ['en'],
                'default_database' => 'default',
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'GeoLite2-City',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                        'url' => sprintf(self::URL, 'GeoLite2-City', 'LICENSE'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-City'),
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
                            'edition' => 'GeoLite2-City',
                            'locales' => ['ru'],
                        ],
                    ],
                ],
            ], [
                'license' => 'LICENSE',
                'databases' => [
                    'default' => [
                        'edition' => 'GeoLite2-City',
                        'locales' => ['ru'],
                        'license' => 'LICENSE',
                        'url' => sprintf(self::URL, 'GeoLite2-City', 'LICENSE'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-City'),
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
                            'edition' => 'GeoLite2-ASN',
                        ],
                        'bar' => [
                            'license' => 'LICENSE_2',
                            'edition' => 'GeoLite2-City',
                            'locales' => ['ru'],
                        ],
                        'baz' => [
                            'license' => 'LICENSE_3',
                            'edition' => 'GeoLite2-Country',
                            'locales' => ['fr', 'en'],
                        ],
                    ],
                ],
            ], [
                'default_database' => 'foo',
                'databases' => [
                    'foo' => [
                        'license' => 'LICENSE1',
                        'edition' => 'GeoLite2-ASN',
                        'url' => sprintf(self::URL, 'GeoLite2-ASN', 'LICENSE1'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-ASN'),
                        'locales' => ['en'],
                    ],
                    'bar' => [
                        'license' => 'LICENSE_2',
                        'edition' => 'GeoLite2-City',
                        'locales' => ['ru'],
                        'url' => sprintf(self::URL, 'GeoLite2-City', 'LICENSE_2'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-City'),
                    ],
                    'baz' => [
                        'license' => 'LICENSE_3',
                        'edition' => 'GeoLite2-Country',
                        'locales' => ['fr', 'en'],
                        'url' => sprintf(self::URL, 'GeoLite2-Country', 'LICENSE_3'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-Country'),
                    ],
                ],
                'locales' => ['en'],
            ]];
            $return[] = [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE_1',
                    'locales' => ['ru', 'en'],
                    'databases' => [
                        'default' => [
                            'edition' => 'GeoLite2-ASN',
                            'locales' => ['fr'],
                        ],
                        'foo' => [
                            'edition' => 'GeoLite2-City',
                            'license' => 'LICENSE_2',
                        ],
                    ],
                ],
            ], [
                'license' => 'LICENSE_1',
                'locales' => ['ru', 'en'],
                'databases' => [
                    'default' => [
                        'edition' => 'GeoLite2-ASN',
                        'locales' => ['fr'],
                        'license' => 'LICENSE_1',
                        'url' => sprintf(self::URL, 'GeoLite2-ASN', 'LICENSE_1'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-ASN'),
                    ],
                    'foo' => [
                        'edition' => 'GeoLite2-City',
                        'license' => 'LICENSE_2',
                        'locales' => ['ru', 'en'],
                        'url' => sprintf(self::URL, 'GeoLite2-City', 'LICENSE_2'),
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-City'),
                    ],
                ],
                'default_database' => 'default',
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
