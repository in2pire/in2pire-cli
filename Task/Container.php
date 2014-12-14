<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Task;

use In2pire\Component\Utility\String;

final class Container
{
    public static function create($taskId, $command)
    {
        static $cache = [];
        $cacheKey = $taskId;

        if (isset($cache[$cacheKey]))  {
            $class = $cache[$cacheKey]['class'];
            $taskId = $cache[$cacheKey]['taskId'];
        } else {
            if (false === strpos($taskId, '.')) {
                // Not FQCN
                $class = __NAMESPACE__ . '\\' . String::convertToCamelCase($taskId);
            } else {
                // FQCN
                $class = explode('.', $taskId);
                $class = array_map(array('In2pire\\Component\\Utility\\String', 'convertToCamelCase'), $class);
                $class = implode('\\', $class);
                $taskId = substr($taskId, strrpos($taskId, '.') + 1);
            }

            $cache[$cacheKey] = [
                'class' => $class,
                'taskId' => $taskId
            ];
        }

        if (!class_exists($class)) {
            throw new \RuntimeException('Unknown task ' . $cacheKey);
        }

        return new $class($command);
    }
}
