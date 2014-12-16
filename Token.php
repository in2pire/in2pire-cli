<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

use In2pire\Component\Utility\NestedArray;

class Token
{
    protected static $listeners = [];

    public static function register($name, $callback)
    {
        static::$listeners[$name] = $callback;
    }

    protected static function invoke($type, $tokens, $data, $options)
    {
        $return = [];

        foreach (static::$listeners as $callback) {
            $result = call_user_func_array($callback, [$type, $tokens, $data, $options]);

            if (isset($result) && is_array($result)) {
                $return = NestedArray::mergeDeep($return, $result);
            } elseif (isset($result)) {
                $return[] = $result;
            }
        }

        return $return;
    }

    public static function replace($text, array $data = [], array $options = [])
    {
        $textTokens = static::scan($text);

        if (empty($textTokens)) {
            return $text;
        }

        $replacements = [];

        foreach ($textTokens as $type => $tokens) {
            $replacements += static::generate($type, $tokens, $data, $options);

            if (!empty($options['clear'])) {
                $replacements += array_fill_keys($tokens, '');
            }
        }

        // Optionally alter the list of replacement values.
        if (!empty($options['callback'])) {
            $function = $options['callback'];
            $function($replacements, $data, $options);
        }

        $tokens = array_keys($replacements);
        $values = array_values($replacements);

        return str_replace($tokens, $values, $text);
    }

    public static function scan($text)
    {
        // Matches tokens with the following pattern: [$type:$name]
        // $type and $name may not contain [ ] characters.
        // $type may not contain : or whitespace characters, but $name may.
        preg_match_all('/
          \[             # [ - pattern start
          ([^\s\[\]:]*)  # match $type not containing whitespace : [ or ]
          :              # : - separator
          ([^\[\]]*)     # match $name not containing [ or ]
          \]             # ] - pattern end
          /x', $text, $matches);

        $types = $matches[1];
        $tokens = $matches[2];

        // Iterate through the matches, building an associative array containing
        // $tokens grouped by $types, pointing to the version of the token found in
        // the source text. For example, $results['node']['title'] = '[node:title]';
        $results = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $results[$types[$i]][$tokens[$i]] = $matches[0][$i];
        }

        return $results;
    }

    public static function generate($type, array $tokens, array $data = [], array $options = [])
    {
        $options += array('sanitize' => true);
        $replacements = Module::invokeAll('token::replace', $type, $tokens, $data, $options);

        // Allow other modules to alter the replacements.
        $context = array(
            'type' => $type,
            'tokens' => $tokens,
            'data' => $data,
            'options' => $options,
        );

        return $replacements;
    }

    public static function findWithPrefix(array $tokens, $prefix, $delimiter = ':')
    {
        $results = [];

        foreach ($tokens as $token => $raw) {
            $parts = explode($delimiter, $token, 2);

            if (count($parts) == 2 && $parts[0] == $prefix) {
                $results[$parts[1]] = $raw;
            }
        }

        return $results;
    }
}
