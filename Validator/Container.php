<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Validator;

use In2pire\Component\Utility\Text;

/**
 * Validator container.
 */
final class Container
{
    /**
     * Create new validator.
     *
     * @param string $validator
     *   Validator ID.
     * @param In2pire\Cli\Command\CliCommand $command
     *   The running command.
     *
     * @return In2pire\Cli\Validator\CliValidator
     *   New validator.
     */
    public static function create($validator, $command)
    {
        static $cache = [];
        $cacheKey = $validator;

        if (isset($cache[$cacheKey]))  {
            $class = $cache[$cacheKey]['class'];
            $validator = $cache[$cacheKey]['validator'];
        } else {
            if (false === strpos($validator, '.')) {
                // Not FQCN
                $class = __NAMESPACE__ . '\\' . String::convertToCamelCase($validator);
            } else {
                // FQCN
                $class = explode('.', $validator);
                $class = array_map(array('In2pire\\Component\\Utility\\Text', 'convertToCamelCase'), $class);
                $class = implode('\\', $class);
                $validator = substr($validator, strrpos($validator, '.') + 1);
            }

            $cache[$cacheKey] = [
                'class' => $class,
                'validator' => $validator
            ];
        }

        if (!class_exists($class)) {
            throw new \RuntimeException('Unknow validator ' . $cacheKey);
        }

        return new $class($command);
    }
}
