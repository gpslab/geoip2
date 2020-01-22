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
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    private static $options = [
        'symfony-app-dir' => 'app',
    ];

    /**
     * @param Event $event
     */
    public static function updateDatabase(Event $event): void
    {
        $options = static::getOptions($event);
        $console_dir = static::getConsoleDir($event, 'clear the cache');

        if (null === $console_dir) {
            return;
        }

        static::executeCommand($event, $console_dir, 'geoip2:update --no-debug', $options['process-timeout']);
    }

    /**
     * @param Event $event
     * @param string $console_dir
     * @param string $cmd
     * @param int $timeout
     */
    protected static function executeCommand(Event $event, string $console_dir, string $cmd, int $timeout = 300): void
    {
        $php = escapeshellarg(self::getPhp(false));
        $php_args = implode(' ', array_map('escapeshellarg', self::getPhpArguments()));
        $console = escapeshellarg($console_dir.'/console');

        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $command = $php.($php_args ? ' '.$php_args : '').' '.$console.' '.$cmd;

        if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
            // Symfony 4.2 +
            $process = Process::fromShellCommandline($command, null, null, null, $timeout);
        } else {
            // Symfony 4.1 and below
            $process = new Process($command, null, null, null, $timeout);
        }

        $process->run(static function ($type, $buffer) use ($event): void {
            $event->getIO()->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\n%s\n\n%s.", escapeshellarg($cmd), $process->getOutput(), $process->getErrorOutput()));
        }
    }

    /**
     * @param Event $event
     *
     * @return array
     */
    protected static function getOptions(Event $event): array
    {
        $options = array_merge(self::$options, $event->getComposer()->getPackage()->getExtra());

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    /**
     * Returns a relative path to the directory that contains the `console` command or null if not found.
     *
     * @param Event  $event       The command event
     * @param string $action_name The name of the action
     *
     * @return string|null
     */
    protected static function getConsoleDir(Event $event, string $action_name): ?string
    {
        $options = static::getOptions($event);

        if (self::useNewDirectoryStructure($options)) {
            if (!self::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $action_name)) {
                return null;
            }

            return $options['symfony-bin-dir'];
        }

        if (!self::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], 'execute command')) {
            return null;
        }

        return $options['symfony-app-dir'];
    }

    /**
     * @param Event  $event
     * @param string $config_name
     * @param string $path
     * @param string $action_name
     *
     * @return bool
     */
    private static function hasDirectory(Event $event, string $config_name, string $path, string $action_name): bool
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf(
                'The %s (%s) specified in composer.json was not found in %s, can not %s.',
                $config_name,
                $path,
                getcwd(),
                $action_name
            ));

            return false;
        }

        return true;
    }

    /**
     * Returns true if the new directory structure is used.
     *
     * @param array $options Composer options
     *
     * @return bool
     */
    private static function useNewDirectoryStructure(array $options): bool
    {
        return isset($options['symfony-bin-dir']) && is_dir($options['symfony-bin-dir']);
    }

    /**
     * Get path to php executable.
     *
     * @param bool $include_args
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    private static function getPhp(bool $include_args = true): string
    {
        $php_finder = new PhpExecutableFinder();
        $php_path = $php_finder->find($include_args);

        if (!$php_path) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $php_path;
    }

    /**
     * @return array
     */
    private static function getPhpArguments(): array
    {
        $arguments = [];
        $php_finder = new PhpExecutableFinder();

        if (method_exists($php_finder, 'findArguments')) {
            $arguments = $php_finder->findArguments();
        }

        $env = (string) getenv('COMPOSER_ORIGINAL_INIS');

        if ($env) {
            $paths = explode(PATH_SEPARATOR, $env);
            $ini = array_shift($paths);
        } else {
            $ini = php_ini_loaded_file();
        }

        if ($ini) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }
}
