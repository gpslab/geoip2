UPGRADE FROM 1.x to 2.0
=======================

### Renamed services

 * The `gpslab.command.geoip2.update` renamed to `GpsLab\Bundle\GeoIP2Bundle\Command\UpdateDatabaseCommand`.

### Removed service

 * The `gpslab.geoip2.component.gzip` service removed.

Updating Dependencies
---------------------

### Removed package

 * The `gpslab/compressor` package removed from dependencies.
 * The `symfony/stopwatch` package removed from dependencies.
