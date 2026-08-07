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
    private $test_filename;

    /**
     * Can not be named "locales": GeoIP2 3.x declares it as a readonly promoted property of the Reader.
     *
     * @var string[]
     */
    private $test_locales;

    /**
     * @param string   $filename
     * @param string[] $locales
     */
    public function __construct(string $filename, array $locales = ['en'])
    {
        $this->test_filename = $filename;
        $this->test_locales = $locales;
        // no call parent for not read database
        // parent::__construct($filename, $locales);
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->test_filename;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->test_locales;
    }
}
