[![Latest Stable Version](https://img.shields.io/packagist/v/gpslab/geoip2.svg?maxAge=3600&label=stable)](https://packagist.org/packages/gpslab/geoip2)
[![Test](https://github.com/gpslab/geoip2/actions/workflows/test.yaml/badge.svg)](https://github.com/gpslab/geoip2/actions/workflows/test.yaml)
[![Coverage Status](https://img.shields.io/coveralls/gpslab/geoip2.svg?maxAge=3600)](https://coveralls.io/github/gpslab/geoip2?branch=master)
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

You can change this URL, for example, if you download the database from a mirror of your own. You can customize the
source URL in the configuration.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
    url: 'https://example.com/GeoLite2-City.tar.gz'
```

#### Proxy

If the outgoing connections of your server go through a proxy, declare the proxy server itself. Do not put it in the
source URL, the URL is the address of the database and not of the proxy.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
    proxy: 'http://proxy.example.com:3128'
```

The proxy is used for downloading the databases of all configured editions. Credentials can be a part of the address,
they are sent in the `Proxy-Authorization` header.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
    proxy: 'http://user:password@proxy.example.com:3128'
```

Url encode a login or a password that contains special characters. Keeping the credentials in a parameter is a better
idea than keeping them in the configuration file.

```yml
gpslab_geoip:
    license: '%env(MAXMIND_LICENSE)%'
    edition: 'GeoLite2-City'
    proxy: '%env(HTTPS_PROXY)%'
```

The `http`, `https`, `tcp` and `ssl` schemes are supported. Use `http` for a plain proxy and `https` for a proxy that
expects a TLS connection to itself. A proxy of any of these schemes can download a database over HTTPS.

### Target download path

By default, new databases downloaded in `%kernel.cache_dir%/{edition_id}.mmdb`, where `edition_id` is a character ID
name from first column on [download page](https://www.maxmind.com/en/download_files). That is, by default, the new
database will be downloaded into folder `var/cache/{env}/`. Keeping the database in the cache folder for each
environment may not be optimal. You can choose a common directory for all environments.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
    path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

#### Permissions

By default, a downloaded database gets the `0755` permissions. A database is a data file and does not need to be
executable, so you may prefer to make it less permissive. The permissions are applied to the databases of all
configured editions.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
    permissions: '0644'
```

Quote the value: the YAML parser does not treat `0644` as an octal number, it returns the `'0644'` string. Both
notations are accepted, as well as the YAML `0o644` and the decimal `420`. The directory of the database is still
created with the `0755` permissions, it has to stay traversable.

#### Localization

By default, the English locale is used for GeoIP record. You can change the locale for record and declare multiple
locales for fallback.

```yml
gpslab_geoip:
    license: 'XXXXXXXXXXXXXXXX'
    edition: 'GeoLite2-City'
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
            url: 'https://example.com/GeoLite2-City.tar.gz' # customize url
            path: '%kernel.project_dir%/var/GeoLite2-City.mmdb' # customize path
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
        $client_locale = $request->getLocale();
        $client_ip = $request->getClientIp();
        $database_name = 'default';
        $fallback_locale = 'en';
    
        $reader = $factory->create($database_name, [$client_locale, $fallback_locale]);
        $record = $reader->city($client_ip);
    
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

Optionally installing splitbrain/php-archive uses significantly less memory when updating a database
and can avoid out of memory errors:

```
composer req splitbrain/php-archive
```

### Download GeoIP database

You can download custom database with console command:

```
php bin/console geoip2:download https://example.com/GeoLite2-City.tar.gz /path/to/GeoLite2-City.mmdb
```

## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
