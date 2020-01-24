UPGRADE FROM 1.x to 2.0
=======================

Renamed configuration option `cache` to `path`.

Before

```yml
gpslab_geoip:
    cache: '%kernel.cache_dir%/GeoLite2-City.mmdb'
```

After

```yml
gpslab_geoip:
    path: '%kernel.cache_dir%/GeoLite2-City.mmdb'
```

Update `composer.json` if you use composer event callbacks.

Before in Symfony <3.0

```json
{
    "scripts": {
        "post-install-cmd": [
            "GpsLab\\Bundle\\GeoIP2Bundle\\Composer\\ScriptHandler::updateDatabase"
        ],
        "post-update-cmd": [
            "GpsLab\\Bundle\\GeoIP2Bundle\\Composer\\ScriptHandler::updateDatabase"
        ]
    }
}
```

Before in Symfony >3.1

```json
{
    "scripts": {
        "symfony-scripts": [
            "GpsLab\\Bundle\\GeoIP2Bundle\\Composer\\ScriptHandler::updateDatabase"
        ]
    }
}
```

After in Symfony >4

```json
{
    "scripts": {
        "auto-scripts": {
              "geoip2:update": "symfony-cmd"
        }
    }
}
```

### Dependencies

 * The `UpdateDatabaseCommand` command not dependency a `CompressorInterface`.

### Renamed


 * The `gpslab.command.geoip2.update` service renamed to `GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand`.

### Removed

 * The `gpslab.geoip2.component.gzip` service removed.
 * The `ScriptHandler` removed.

Updating Dependencies
---------------------

### Require PHP extensions

 * Require the [Phar](https://www.php.net/manual/en/book.phar.php) extension.
 * Require the [Zlib](https://www.php.net/manual/en/book.zlib.php) extension.

### Require packages

 * Require the `symfony/filesystem` package.
 * Require the `symfony/config` package.
 * The `symfony/http-kernel` package moved from `require-dev` to `require`.
 * The `symfony/dependency-injection` package moved from `require-dev` to `require`.
 * The `symfony/expression-language` package moved from `require-dev` to `require`.
 * The `symfony/console` package moved from `require-dev` to `require`.

### Removed packages

 * The `symfony/stopwatch` package removed from dependencies.
 * The `gpslab/compressor` package removed from dependencies.
 * The `scrutinizer/ocular` package removed from dependencies.
 * The `satooshi/php-coveralls` package removed from dependencies.
