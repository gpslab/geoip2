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
composer req gpslab/geoip2
```

## Configuration

To configure auto-update the database you need to generate your personal licence key.

#### Steps for generate licence key

1. [Sign up for a MaxMind account](https://www.maxmind.com/en/geolite2/signup) (no purchase required)
2. Login and generate a [licence key](https://www.maxmind.com/en/accounts/current/license-key)
3. Save your licence key
4. Open [download page](https://www.maxmind.com/en/download_files) and find your needed DB edition `ID` and copy value
from first column.

<p align="center">
    <a href="https://user-images.githubusercontent.com/2862833/72380833-4ccd5a00-3727-11ea-9c6c-aecd55c086ed.png">
        <img src="https://user-images.githubusercontent.com/2862833/72380833-4ccd5a00-3727-11ea-9c6c-aecd55c086ed.png" alt="GeoIP2 download page">
    </a>
</p>

Example configuration:

```yml
gpslab_geoip:
    # Your personal licence key
    license: 'XXXXXX'

    # Database edition ID
    edition: 'GeoLite2-City'
```

#### Database source URL

By default, this URL is used to download a new databases
`https://download.maxmind.com/app/geoip_download?edition_id={edition_id}&license_key={license_key}&suffix=tar.gz`

* `edition_id` - character ID name from first column on [download page](https://www.maxmind.com/en/download_files);
* `license_key` - your personal [licence key](https://www.maxmind.com/en/accounts/current/license-key).

You can change this URL, for example, if you want to use a proxy to download the database. You can customize the source
URL in the configuration.

```yml
gpslab_geoip:
    url: 'https://example.com/GeoLite2-City.tar.gz'
```

### Target download path

By default, new databases downloaded in `%kernel.cache_dir%/{edition_id}.mmdb`, where `edition_id` is a character ID
name from first column on [download page](https://www.maxmind.com/en/download_files). That is, by default, the new
database will be downloaded into folder `var/cache/{env}/`. Keeping the database in the cache folder for each
environment may not be optimal. You can choose a common directory for all environments.

```yml
gpslab_geoip:
    path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

#### Localization

By default, the English locale is used for PGeoIP record. You can change the locale for record and declare multiple
locales for fallback.

```yml
gpslab_geoip:
    locales: [ 'ru', 'en' ]
```

## Usage

You can get GeoIP2 reader service:

```php
use GeoIp2\Database\Reader;

// get a GeoIP2 City model
$record = $this->get(Reader::class)->city('128.101.101.101');

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

## Multiple databases

You can use multiple GeoIP databases in one application. Need update configuration file. 

```yml
gpslab_geoip:
    databases:
        default:
            license: 'XXXXXX'
            edition: 'GeoLite2-City'
        country:
            license: 'XXXXXX'
            edition: 'GeoLite2-Country'
        asn:
            license: 'XXXXXX'
            edition: 'GeoLite2-ASN'
```

Using in application:

```yml
// get a GeoIP2 City model
$default_reader = $this->get('geoip2.database.default_reader');

// get a GeoIP2 Country model
$country_reader = $this->get('geoip2.database.country_reader');

// get a GeoIP2 ASN model
$asn_reader = $this->get('geoip2.database.asn_reader');
```

You can rename the default database.

```yml
gpslab_geoip:
    default_database: 'city'
    databases:
        asn:
            license: 'XXXXXX'
            edition: 'GeoLite2-ASN'
        city:
            license: 'XXXXXX'
            edition: 'GeoLite2-City'
        country:
            license: 'XXXXXX'
            edition: 'GeoLite2-Country'
```

## Update GeoIP database

Execute command for update database:

```
php bin/console geoip2:update
```

## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
