<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Command;

use In2pire\Component\Utility\String;

/**
 * Command container.
 */
final class Container
{
    /**
     * Create command from id.
     *
     * @param string $command
     *   Command ID.
     * @param In2pire\Cli\CliApplication $app
     *   The running application
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The created command.
     */
    public static function create($command, $app)
    {
        static $cache = [];
        $cacheKey = $command;

        if (isset($cache[$cacheKey]))  {
            $class = $cache[$cacheKey]['class'];
            $command = $cache[$cacheKey]['command'];
        } else {
            if (false === strpos($command, '.')) {
                // Not FQCN
                $class = __NAMESPACE__ . '\\' . String::convertToCamelCase($command);
            } else {
                // FQCN
                $class = explode('.', $command);
                $class = array_map(array('String', 'convertToCamelCase'), $class);
                $class = implode('\\', $class);
                $command = substr($command, strrpos($command, '.') + 1);
            }

            $cache[$cacheKey] = [
                'class' => $class,
                'command' => $command
            ];
        }

        if (!class_exists($class)) {
            throw new \RuntimeException('Unknow command ' . $command);
        }

        return new $class($app);
    }
}
