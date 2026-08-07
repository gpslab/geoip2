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

/**
 * Stream wrapper for test the stream context a database is downloaded with.
 */
class ProxyStreamWrapper
{
    public const PROTOCOL = 'geoip2test';

    /**
     * Options of the stream context the wrapper was opened with.
     *
     * @var array<string, array<string, mixed>>
     */
    public static $context_options = [];

    /**
     * Content to return for a read.
     *
     * @var string
     */
    public static $content = '';

    /**
     * Set by PHP for a stream opened with a context.
     *
     * @var resource|null
     */
    public $context;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param string $content
     */
    public static function register(string $content): void
    {
        self::unregister();

        self::$context_options = [];
        self::$content = $content;

        stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public static function unregister(): void
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    /**
     * @param string      $path
     * @param string      $mode
     * @param int         $options
     * @param string|null $opened_path
     *
     * @return bool
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
    {
        self::$context_options = is_resource($this->context) ? stream_context_get_options($this->context) : [];
        $this->position = 0;

        return true;
    }

    /**
     * @param int $count
     *
     * @return string
     */
    public function stream_read(int $count): string
    {
        $chunk = substr(self::$content, $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    /**
     * @return bool
     */
    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$content);
    }

    /**
     * @return array<int|string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }

    public function stream_close(): void
    {
    }
}
