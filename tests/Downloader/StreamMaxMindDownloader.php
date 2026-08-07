<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Downloader;

use GpsLab\Bundle\GeoIP2Bundle\Downloader\MaxMindDownloader;

/**
 * Downloader that always takes the PHP streams path, whether the cURL extension is installed or not.
 */
class StreamMaxMindDownloader extends MaxMindDownloader
{
    /**
     * @return bool
     */
    protected function isCurlAvailable(): bool
    {
        return false;
    }
}
