<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Reader;

use GeoIp2\Database\Reader;

final class ReaderFactory
{
    /**
     * @var array[]
     */
    private $databases;

    /**
     * @var string
     */
    private $reader_class;

    /**
     * @param array[] $databases
     * @param string  $reader_class
     */
    public function __construct(array $databases, string $reader_class = Reader::class)
    {
        $this->databases = $databases;
        $this->reader_class = $reader_class;
    }

    /**
     * @param string        $database
     * @param string[]|null $locales
     *
     * @return Reader
     */
    public function create(string $database, ?array $locales = null): Reader
    {
        if (!array_key_exists($database, $this->databases)) {
            $databases = implode('", "', array_keys($this->databases));

            throw new \InvalidArgumentException(sprintf('Undefined "%s" database. Available "%s" databases.', $database, $databases));
        }

        if (!is_array($this->databases[$database]) || empty($this->databases[$database]['path'])) {
            throw new \InvalidArgumentException(sprintf('Database "%s" is not configured.', $database));
        }

        if ($locales === null) {
            $locales = $this->databases[$database]['locales'] ?? ['en'];
        }

        return new $this->reader_class($this->databases[$database]['path'], $locales);
    }
}
