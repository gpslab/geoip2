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
