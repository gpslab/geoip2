UPGRADE FROM 1.x to 2.0
=======================

Update `composer.json` if you use composer vent callbacks.

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

### Removed package

 * The `gpslab/compressor` package removed from dependencies.
 * The `symfony/stopwatch` package removed from dependencies.
