[![Latest Stable Version](https://img.shields.io/packagist/v/gpslab/geoip2.svg?maxAge=3600&label=stable)](https://packagist.org/packages/gpslab/geoip2)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/gpslab/geoip2.svg?maxAge=3600&label=unstable)](https://packagist.org/packages/gpslab/geoip2)
[![Total Downloads](https://img.shields.io/packagist/dt/gpslab/geoip2.svg?maxAge=3600)](https://packagist.org/packages/gpslab/geoip2)
[![Build Status](https://img.shields.io/travis/gpslab/geoip2.svg?maxAge=3600)](https://travis-ci.org/gpslab/geoip2)
[![Coverage Status](https://img.shields.io/coveralls/gpslab/geoip2.svg?maxAge=3600)](https://coveralls.io/github/gpslab/geoip2?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/gpslab/geoip2.svg?maxAge=3600)](https://scrutinizer-ci.com/g/gpslab/geoip2/?branch=master)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/21b2bef1-ea4f-4fe9-a82a-dc5e70616b66.svg?maxAge=3600&label=SLInsight)](https://insight.sensiolabs.com/projects/21b2bef1-ea4f-4fe9-a82a-dc5e70616b66)
[![StyleCI](https://styleci.io/repos/79822037/shield?branch=master)](https://styleci.io/repos/79822037)
[![License](https://img.shields.io/packagist/l/gpslab/geoip2.svg?maxAge=3600)](https://github.com/gpslab/geoip2)

A Symfony Bundle for the GeoIP2 API
====================================

Bundle for use [maxmind/GeoIP2](https://github.com/maxmind/GeoIP2-php) in Symfony.

## Installation

Pretty simple with [Composer](http://packagist.org), run:

```sh
composer require gpslab/date-bundle
```

Add GpsLabDateBundle to your application kernel

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new GpsLab\Bundle\GeoIP2Bundle\GpsLabGeoIP2Bundle(),
        // ...
    );
}
```

## License

This bundle is under the [MIT license](http://opensource.org/licenses/MIT). See the complete license in the file: LICENSE
