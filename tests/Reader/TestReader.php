<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Tests\Reader;

use GeoIp2\Database\Reader;

/**
 * Test class for test the initialization of a Reader object without reading the database file.
 */
class TestReader extends Reader
{
    /**
     * @var string
     */
    public $filename;

    /**
     * @var string[]
     */
    public $locales;

    /**
     * @param string   $filename
     * @param string[] $locales
     */
    public function __construct(string $filename, array $locales = ['en'])
    {
        $this->filename = $filename;
        $this->locales = $locales;
        // no call parent for not read database
        // parent::__construct($filename, $locales);
    }
}
