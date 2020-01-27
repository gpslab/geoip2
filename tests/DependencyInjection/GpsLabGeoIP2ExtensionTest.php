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

use GeoIp2\Database\Reader;
use GpsLab\Bundle\GeoIP2Bundle\Command\DownloadDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand;
use GpsLab\Bundle\GeoIP2Bundle\DependencyInjection\GpsLabGeoIP2Extension;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\Downloader;
use GpsLab\Bundle\GeoIP2Bundle\Downloader\MaxMindDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GpsLabGeoIP2ExtensionTest extends TestCase
{
    /**
     * @var GpsLabGeoIP2Extension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new GpsLabGeoIP2Extension();
    }

    /**
     * @return mixed[]
     */
    public function getCacheDirs(): array
    {
        return [
            [null],
            ['/tmp'],
            [false],
        ];
    }

    /**
     * @dataProvider getCacheDirs
     *
     * @param mixed $cache_dir
     */
    public function testLoad($cache_dir): void
    {
        $configs = [
            'gpslab_geoip' => [
                'default_database' => 'city',
                'databases' => [
                    'asn' => [
                        'license' => 'XXXXXX',
                        'edition' => 'GeoLite2-ASN',
                    ],
                    'city' => [
                        'license' => 'YYYYYY',
                        'edition' => 'GeoLite2-City',
                        'locales' => ['ru'],
                    ],
                    'country' => [
                        'license' => 'ZZZZZZ',
                        'edition' => 'GeoLite2-Country',
                        'locales' => ['fr'],
                    ],
                ],
            ],
        ];

        $container = new ContainerBuilder();

        if ($cache_dir !== null) {
            $container->setParameter('kernel.cache_dir', $cache_dir);
        }

        $extension = new GpsLabGeoIP2Extension();
        $extension->load($configs, $container);

        $default_reader_service_name = sprintf(
            'geoip2.database.%s_reader',
            $configs['gpslab_geoip']['default_database']
        );

        $this->assertTrue($container->hasAlias('geoip2.reader'));
        $alias = $container->getAlias('geoip2.reader');
        $this->assertTrue($alias->isPublic());
        $this->assertSame($default_reader_service_name, (string) $alias);

        $this->assertTrue($container->hasAlias(Reader::class));
        $alias = $container->getAlias(Reader::class);
        $this->assertTrue($alias->isPublic());
        $this->assertSame($default_reader_service_name, (string) $alias);

        $this->assertTrue($container->hasAlias(Downloader::class));
        $alias = $container->getAlias(Downloader::class);
        $this->assertTrue($alias->isPublic());
        $this->assertContains((string) $alias, [
            MaxMindDownloader::class, // Symfony >= 4.0
            strtolower(MaxMindDownloader::class) // Symfony < 4.0
        ]);

        $databases = [];
        foreach ($configs['gpslab_geoip']['databases'] as $name => $database) {
            $service_name = sprintf('geoip2.database.%s_reader', $name);

            $this->assertTrue($container->hasDefinition($service_name));
            $reader = $container->getDefinition($service_name);
            $this->assertTrue($reader->isPublic());
            $this->assertTrue($reader->isLazy());
            $this->assertSame(Reader::class, $reader->getClass());
            $path = sprintf('%s/%s.mmdb', $cache_dir ?: '/tmp', $database['edition']);
            $this->assertSame($path, $reader->getArgument(0));
            $this->assertSame($database['locales'] ?? ['en'], $reader->getArgument(1));

            $url = 'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz';
            $url = sprintf($url, $database['edition'], $database['license']);
            $databases[$name] = $database;
            $databases[$name]['path'] = $path;
            $databases[$name]['locales'] = $database['locales'] ?? ['en'];
            $databases[$name]['url'] = $url;
            ksort($databases[$name]);
        }

        $this->assertTrue($container->hasDefinition(MaxMindDownloader::class));
        $downloader = $container->getDefinition(MaxMindDownloader::class);
        $this->assertFalse($downloader->isPublic()); // isPrivate() allowed in Symfony >= 3.4
        $this->assertInstanceOf(Reference::class, $downloader->getArgument(0));
        $this->assertSame('filesystem', (string) $downloader->getArgument(0));
        $this->assertInstanceOf(Reference::class, $downloader->getArgument(1));
        $this->assertSame('logger', (string) $downloader->getArgument(1));

        $this->assertTrue($container->hasDefinition(UpdateDatabaseCommand::class));
        $update_command = $container->getDefinition(UpdateDatabaseCommand::class);
        $this->assertFalse($update_command->isPublic()); // isPrivate() allowed in Symfony >= 3.4
        $this->assertTrue($update_command->hasTag('console.command'));
        $this->assertInstanceOf(Reference::class, $update_command->getArgument(0));
        $this->assertContains((string) $update_command->getArgument(0), [
            Downloader::class, // Symfony >= 4.0
            strtolower(Downloader::class) // Symfony < 4.0
        ]);
        $this->assertIsArray($update_command->getArgument(1));
        $this->assertSame(array_keys($databases), array_keys($update_command->getArgument(1)));
        foreach ($update_command->getArgument(1) as $name => $database) {
            ksort($database);
            $this->assertSame($databases[$name], $database);
        }

        $this->assertTrue($container->hasDefinition(DownloadDatabaseCommand::class));
        $download_command = $container->getDefinition(DownloadDatabaseCommand::class);
        $this->assertFalse($download_command->isPublic()); // isPrivate() allowed in Symfony >= 3.4
        $this->assertTrue($download_command->hasTag('console.command'));
        $this->assertInstanceOf(Reference::class, $download_command->getArgument(0));
        $this->assertContains((string) $update_command->getArgument(0), [
            Downloader::class, // Symfony >= 4.0
            strtolower(Downloader::class) // Symfony < 4.0
        ]);
    }

    public function testGetAlias(): void
    {
        $this->assertSame('gpslab_geoip', $this->extension->getAlias());
    }
}
