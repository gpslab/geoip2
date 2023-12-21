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

    private const DATABASE_EDITION_IDS = [
        'GeoLite2-ASN',
        'GeoLite2-City',
        'GeoLite2-Country',
        'GeoIP2-City',
        'GeoIP2-Country',
        'GeoIP2-Anonymous-IP',
        'GeoIP2-Domain',
        'GeoIP2-ISP',
    ];

    /**
     * @return mixed[]
     */
    public function getBadConfigs(): iterable
    {
        $configurations = [];
        // undefined edition
        $configurations[] = [
            'gpslab_geoip' => [
                'license' => 'LICENSE',
            ],
        ];
        // available values:
        //   GeoLite2-ASN
        //   GeoLite2-City
        //   GeoLite2-Country
        //   GeoIP2-City
        //   GeoIP2-Country
        //   GeoIP2-Anonymous-IP
        //   GeoIP2-Domain
        //   GeoIP2-ISP
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

        foreach ($configurations as $configuration) {
            foreach (['/tmp/var/cache', null] as $cache_dir) {
                yield [$cache_dir, $configuration];
            }
        }
    }

    /**
     * @dataProvider getBadConfigs
     *
     * @param string|null         $cache_dir
     * @param array<array<mixed>> $configs
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
     * @return array<array<mixed>>
     */
    public function getConfigs(): iterable
    {
        foreach (['/tmp/var/cache', null] as $cache_dir) {
            $real_cache_dir = $cache_dir ?: sys_get_temp_dir();

            yield [$cache_dir, [], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => null,
            ], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => [],
            ], [
                'locales' => ['en'],
                'default_database' => 'default',
                'databases' => [],
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => null,
                ],
            ], [
                'databases' => [],
                'locales' => ['en'],
                'default_database' => 'default',
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [],
                ],
            ], [
                'databases' => [],
                'locales' => ['en'],
                'default_database' => 'default',
            ]];

            foreach (self::DATABASE_EDITION_IDS as $database_edition_id) {
                yield [$cache_dir, [
                    'gpslab_geoip' => [
                        'license' => 'LICENSE',
                        'edition' => $database_edition_id,
                    ],
                ], [
                    'default_database' => 'default',
                    'databases' => [
                        'default' => [
                            'license' => 'LICENSE',
                            'edition' => $database_edition_id,
                            'url' => sprintf(self::URL, $database_edition_id, 'LICENSE'),
                            'path' => sprintf(self::PATH, $real_cache_dir, $database_edition_id),
                            'locales' => ['en'],
                        ],
                    ],
                    'locales' => ['en'],
                ]];

                yield [$cache_dir, [
                    'gpslab_geoip' => [
                        'license' => 'LICENSE',
                        'databases' => [
                            'default' => [
                                'edition' => $database_edition_id,
                                'locales' => ['ru'],
                            ],
                        ],
                    ],
                ], [
                    'license' => 'LICENSE',
                    'databases' => [
                        'default' => [
                            'edition' => $database_edition_id,
                            'locales' => ['ru'],
                            'license' => 'LICENSE',
                            'url' => sprintf(self::URL, $database_edition_id, 'LICENSE'),
                            'path' => sprintf(self::PATH, $real_cache_dir, $database_edition_id),
                        ],
                    ],
                    'default_database' => 'default',
                    'locales' => ['en'],
                ]];
            }

            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'GeoLite2-City',
                    'url' => 'https://example.com/GeoLite2-City.tar.gz',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                        'url' => 'https://example.com/GeoLite2-City.tar.gz',
                        'path' => sprintf(self::PATH, $real_cache_dir, 'GeoLite2-City'),
                        'locales' => ['en'],
                    ],
                ],
                'locales' => ['en'],
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'LICENSE',
                    'edition' => 'GeoLite2-City',
                    'path' => '%kernel.project_dir%/var/GeoLite2-City.mmdb',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [
                    'default' => [
                        'license' => 'LICENSE',
                        'edition' => 'GeoLite2-City',
                        'path' => '%kernel.project_dir%/var/GeoLite2-City.mmdb',
                        'url' => sprintf(self::URL, 'GeoLite2-City', 'LICENSE'),
                        'locales' => ['en'],
                    ],
                ],
                'locales' => ['en'],
            ]];

            yield [$cache_dir, [
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
                            'url' => 'https://example.com/GeoLite2-City.tar.gz',
                            'path' => '%kernel.project_dir%/var/GeoLite2-City.mmdb',
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
                        'url' => 'https://example.com/GeoLite2-City.tar.gz',
                        'path' => '%kernel.project_dir%/var/GeoLite2-City.mmdb',
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

            yield [$cache_dir, [
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

            // test dirty hack for Symfony Flex
            // https://github.com/symfony/recipes-contrib/pull/837
            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'license' => 'YOUR-LICENSE-KEY',
                ],
            ], [
                'default_database' => 'default',
                'databases' => [],
                'locales' => ['en'],
            ]];

            yield [$cache_dir, [
                'gpslab_geoip' => [
                    'databases' => [
                        'default' => [
                            'license' => 'YOUR-LICENSE-KEY',
                        ],
                    ],
                ],
            ], [
                'databases' => [],
                'default_database' => 'default',
                'locales' => ['en'],
            ]];
        }
    }

    /**
     * @dataProvider getConfigs
     *
     * @param string|null         $cache_dir
     * @param array<array<mixed>> $configs
     * @param array<array<mixed>> $expected
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
