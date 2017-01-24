<?php
/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ScriptHandler
{
    /**
     * @var string|null|false
     */
    private static $php_path = null;

    /**
     * @param Event $event
     */
    public static function updateDatabase(Event $event)
    {
        self::executeCommand($event, 'geoip2:update --no-debug');
    }

    /**
     * @param Event $event
     * @param string $cmd
     * @param int $timeout
     */
    private static function executeCommand(Event $event, $cmd, $timeout = 300)
    {
        if ($event->getIO()->isDecorated()) {
            $cmd .= ' --ansi';
        }

        $php = escapeshellarg(self::getPhp());

        $process = new Process($php.' app/console '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) {
            $event->getIO()->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                "An error occurred when executing the \"%s\" command:\n\n%s\n\n%s.",
                escapeshellarg($cmd),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }
    }

    /**
     * Get path to php executable.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    private static function getPhp()
    {
        if (is_null(self::$php_path)) {
            $finder = new PhpExecutableFinder();
            if (!(self::$php_path = $finder->find())) {
                throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
            }
        }

        return self::$php_path;
    }
}
