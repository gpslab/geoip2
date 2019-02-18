[![Latest Stable Version](https://img.shields.io/packagist/v/gpslab/geoip2.svg?maxAge=3600&label=stable)](https://packagist.org/packages/gpslab/geoip2)
[![PHP from Travis config](https://img.shields.io/travis/php-v/gpslab/geoip2.svg?maxAge=3600)](https://packagist.org/packages/gpslab/geoip2)
[![Build Status](https://img.shields.io/travis/gpslab/geoip2.svg?maxAge=3600)](https://travis-ci.org/gpslab/geoip2)
[![Coverage Status](https://img.shields.io/coveralls/gpslab/geoip2.svg?maxAge=3600)](https://coveralls.io/github/gpslab/geoip2?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/gpslab/geoip2.svg?maxAge=3600)](https://scrutinizer-ci.com/g/gpslab/geoip2/?branch=master)
[![StyleCI](https://styleci.io/repos/79822037/shield?branch=master)](https://styleci.io/repos/79822037)
[![License](https://img.shields.io/packagist/l/gpslab/geoip2.svg?maxAge=3600)](https://github.com/gpslab/geoip2)

A Symfony Bundle for the Maxmind GeoIP2 API
===========================================

Bundle for use [maxmind/GeoIP2](https://github.com/maxmind/GeoIP2-php) in Symfony.

## Installation

Pretty simple with [Composer](http://packagist.org), run:

```sh
composer require gpslab/geoip2
```

Add GpsLabGeoIP2Bundle to your application kernel

**Symfony <3.4**

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new GpsLab\Bundle\GeoIP2Bundle\GpsLabGeoIP2Bundle(),
    );
}
```

**Symfony >4.0**

```php
// config/bundles.php
return [
    // ...
    GpsLab\Bundle\GeoIP2Bundle\GpsLabGeoIP2Bundle::class => ['all' => true],
];
```

## Configuration

Default configuration:

```yml
gpslab_geoip:
    # Path to download GeoIP database.
    # It's a default value. You can change it.
    cache: '%kernel.cache_dir%/GeoLite2-City.mmdb'

    # URL for download new GeoIP database.
    # It's a default value. You can change it.
    url: 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz'

    # Get model data in this locale
    # It's a default value. You can change it.
    locales: [ '%locale%' ]
```

## Usage

You can get GeoIP2 reader service:

```php
// get a GeoIP2 City model
$record = $this->get('geoip2.reader')->city('128.101.101.101');

print($record->country->isoCode . "\n"); // 'US'
print($record->country->name . "\n"); // 'United States'
print($record->country->names['zh-CN'] . "\n"); // '美国'

print($record->mostSpecificSubdivision->name . "\n"); // 'Minnesota'
print($record->mostSpecificSubdivision->isoCode . "\n"); // 'MN'

print($record->city->name . "\n"); // 'Minneapolis'

print($record->postal->code . "\n"); // '55455'

print($record->location->latitude . "\n"); // 44.9733
print($record->location->longitude . "\n"); // -93.2323
```

For more example see the [GeoIP2](https://github.com/maxmind/GeoIP2-php) library.

## Update GeoIP database

### From command line

Execute command for update database:

**Symfony <2.8**

```
php app/console geoip2:update
```

**Symfony >3.0**

```
php bin/console geoip2:update
```

### From composer

Add to your `composer.json` event callbacks in a `scripts` section:


**Symfony <3.0**

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

**Symfony >3.1**

```json
{
    "scripts": {
        "symfony-scripts": [
            "GpsLab\\Bundle\\GeoIP2Bundle\\Composer\\ScriptHandler::updateDatabase"
        ]
    }
}
```


## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
