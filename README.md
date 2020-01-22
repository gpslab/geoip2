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

**Attention!** MaxMind [changed](https://blog.maxmind.com/2019/12/18/significant-changes-to-accessing-and-using-geolite2-databases/)
their policy and user agreements about using their data so old URLs like
`https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz` are no working anymore.

**Steps for Migration**

1. [Sign up for a MaxMind account](https://www.maxmind.com/en/geolite2/signup) (no purchase required)
2. Login and generate a [licence key](https://www.maxmind.com/en/accounts/current/license-key)
3. Save your licence key
4. Open [download page](https://www.maxmind.com/en/download_files) and find your needed DB edition `ID` and copy value
from first column.
![GeoIP2 download page](https://user-images.githubusercontent.com/2862833/72380833-4ccd5a00-3727-11ea-9c6c-aecd55c086ed.png)
5. Open your config file with `gpslab_geoip` params and replace `url` param by next:
`https://download.maxmind.com/app/geoip_download?edition_id={edition_id}&license_key={license_key}&suffix=tar.gz`

* `ID` - character ID name from first column on download page
* `license_key` - your saved licence key
 
```yml
gpslab_geoip:
    # URL for download new GeoIP database.
    # You should change {edition_id} and {license_key} in URL to your values.
    url: 'https://download.maxmind.com/app/geoip_download?edition_id={edition_id}&license_key={license_key}&suffix=tar.gz'

    # Path to download GeoIP database.
    # It's a default value. You can change it.
    cache: '%kernel.cache_dir%/GeoLite2-City.mmdb'

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

Execute command for update database:

```
php bin/console geoip2:update
```

## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
