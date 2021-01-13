[![Latest Stable Version](https://img.shields.io/packagist/v/gpslab/geoip2.svg?maxAge=3600&label=stable)](https://packagist.org/packages/gpslab/geoip2)
[![Build Status](https://img.shields.io/travis/gpslab/geoip2.svg?maxAge=3600)](https://travis-ci.org/gpslab/geoip2)
[![Coverage Status](https://img.shields.io/coveralls/gpslab/geoip2.svg?maxAge=3600)](https://coveralls.io/github/gpslab/geoip2?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/gpslab/geoip2.svg?maxAge=3600)](https://scrutinizer-ci.com/g/gpslab/geoip2/?branch=master)
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

Example configuration:

```yml
gpslab_geoip:
    # Your personal licence key
    license: 'XXXXXXXXXXXXXXXX'

    # One of database edition IDs:
    #   GeoLite2-ASN
    #   GeoLite2-City
    #   GeoLite2-Country
    #   GeoIP2-City
    #   GeoIP2-Country
    #   GeoIP2-Anonymous-IP
    #   GeoIP2-Domain
    #   GeoIP2-ISP
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

By default, the English locale is used for GeoIP record. You can change the locale for record and declare multiple
locales for fallback.

```yml
gpslab_geoip:
    locales: [ 'ru', 'en' ]
```

## Usage

You can get GeoIP2 reader service:

```php
use GeoIp2\Database\Reader;

// get a GeoIP2 reader
$reader = $this->get(Reader::class);
// or
//$reader = $this->get('geoip2.reader');

// get a GeoIP2 City model
$record = $reader->city('128.101.101.101');

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
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-City'
        country:
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-Country'
        asn:
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-ASN'
```

Using in application:

```php
// get a GeoIP2 reader for City database
$default_reader = $this->get('geoip2.database.default_reader');
// or
//$default_reader = $this->get(Reader::class);
// or
//$default_reader = $this->get('geoip2.reader');

// get a GeoIP2 reader for Country database
$country_reader = $this->get('geoip2.database.country_reader');

// get a GeoIP2 reader for ASN database
$asn_reader = $this->get('geoip2.database.asn_reader');
```

You can rename the default database.

```yml
gpslab_geoip:
    default_database: 'city'
    databases:
        asn:
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-ASN'
        city:
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-City'
        country:
            license: 'XXXXXXXXXXXXXXXX'
            edition: 'GeoLite2-Country'
```

```php
// get a GeoIP2 reader for City database
$default_reader = $this->get('geoip2.database.city_reader');
// or
//$default_reader = $this->get(Reader::class);
// or
//$default_reader = $this->get('geoip2.reader');
```

In order not to repeat the license key and locales for each database, you can specify them once.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX' # global license
    locales: [ 'ru', 'en' ] # global locales
    default_database: 'city'
    databases:
        asn:
            edition: 'GeoLite2-ASN'
            locales: [ 'fr' ] # customize locales
        city:
            edition: 'GeoLite2-City'
        country:
            edition: 'GeoLite2-Country'
            license: 'YYYYYYYYYYYYYYYY' # customize license
```

### GeoIP data in client locale

If you want to show the GeoIP data to the user and show them in the user locale, then you can use the reader factory.

```php
use GpsLab\Bundle\GeoIP2Bundle\Reader\ReaderFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeoIPController
{
    public function index(Request $request, ReaderFactory $factory): Response
    {
        $locale = $request->getLocale();
        $ip = $request->getClientIp();
    
        $reader = $factory->create('default', [$locale, 'en' /* fallback */]);
        $record = $reader->city($ip);
    
        return new Response(sprintf('You are from %s?', $record->country->name));
    }
}
```

## Console commands

### Update GeoIP database

Execute console command for update all databases:

```
php bin/console geoip2:update
```

If you use multiple databases, then for config:

```yml
gpslab_geoip:
    # ...
    databases:
        asn:
            # ...
        city:
            # ...
        country:
            # ...
```

You can update several databases:

```
php bin/console geoip2:update city country
```

### Download GeoIP database

You can download custom database with console command:

```
php bin/console geoip2:download https://example.com/GeoLite2-City.tar.gz /path/to/GeoLite2-City.mmdb
```

## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
