<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Question;

namespace In2pire\Component\Utility\String;

final class Container
{
    public static function create($question, $command)
    {
        static $cache = [];
        $cacheKey = $question;

        if (isset($cache[$cacheKey]))  {
            $class = $cache[$cacheKey]['class'];
            $question = $cache[$cacheKey]['question'];
        } else {
            if (false === strpos($question, '.')) {
                // Not FQCN
                $class = __NAMESPACE__ . '\\' . String::convertToCamelCase($question);
            } else {
                // FQCN
                $class = explode('.', $question);
                $class = array_map(array('String', 'convertToCamelCase'), $class);
                $class = implode('\\', $class);
                $question = substr($question, strrpos($question, '.') + 1);
            }

            $cache[$cacheKey] = [
                'class' => $class,
                'question' => $question
            ];
        }


        $class = __NAMESPACE__ . '\\' . Utility::convertToCamelCase($question);

        if (!class_exists($class)) {
            throw new \RuntimeException('Unknow question ' . $question);
        }

        return new $class($command);
    }
}
