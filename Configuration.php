<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

use Symfony\Component\Yaml\Yaml;
use In2pire\Component\Utility\NestedArray;

/**
 * Configuration.
 */
final class Configuration
{
    protected static $confPath = APP_CONF_PATH;

    protected static function setConfigPath($path)
    {
        static::$confPath = $path;
    }

    /**
     * Get path to configuration folder.
     *
     * @return string
     *   Path.
     */
    protected static function getConfigPath()
    {
        return static::$confPath;
    }

    /**
     * Get config file path.
     *
     * @param string $namespace
     *   Configuration namespace.
     *
     * @return string
     *   Path to configuration file.
     */
    protected static function getConfigFile($namespace)
    {
        if (empty($namespace)) {
            throw new \UnexpectedValueException('Namespace is empty');
        }

        return static::getConfigPath() . '/' . str_replace('.', '/', $namespace) . '.yml';
    }

    /**
     * Load configuration from namespace.
     *
     * @param string $namespace
     *   Configuration namespace.
     * @param  boolean $require
     *   (optional) Throw expcetion if namespace configuration file could not be
     *   found.
     *
     * @return mixed|null
     *   List of configurations or null if file not found.
     */
    protected static function load($namespace, $require = false)
    {
        // Static cache.
        static $configurations = [];

        // If cache is set.
        if (isset($configurations[$namespace])) {
            return $configurations[$namespace];
        }

        $file = static::getConfigFile($namespace);

        if (!file_exists($file)) {
            if ($require) {
                throw new \RuntimeException('Could not find settings file for ' . $namespace);
            }

            return null;
        }

        // Parse configuration.
        $configuration = Yaml::parse($file);

        if (is_array($configuration) && !empty($configuration['inherits'])) {
            $allConfiguration = [];

            foreach ($configuration['inherits'] as $parentNamespace) {
                $allConfiguration[] = static::load($parentNamespace);
            }

            $allConfiguration[] = $configuration;
            $configuration = static::merge($allConfiguration);
            unset($configuration['inherits']);
        }

        return $configurations[$namespace] = $configuration;
    }

    /**
     * Get all configuration from namespace
     *
     * @param string $namespace
     *   Configuration namespace.
     * @param  boolean $require
     *   (optional) Throw expcetion if namespace configuration file could not be
     *   found.
     *
     * @return mixed|null
     *   List of configurations or null if file not found.
     *
     * @see In2pire\Memcached\Configuration::load()
     */
    public static function getAll($namespace, $require = false)
    {
        return static::load($namespace, $require);
    }

    /**
     * Get a configuration from namespace
     *
     * @param string $namespace
     *   Configuration $name.
     * @param string $namespace
     *   Configuration name.
     * @param mixed $default
     *   (optional) Default value if cannot find configuration.
     * @param  boolean $require
     *   (optional) Throw expcetion if namespace configuration file could not be
     *   found.
     *
     * @return mixed|null
     *   Configurations or null if file not found.
     *
     * @see In2pire\Memcached\Configuration::load()
     */
    public static function get($namespace, $name, $default = null, $require = false)
    {
        $configuration = static::load($namespace, $require);
        return array_key_exists($name, $configuration) ? $configuration[$name] : $default;
    }

    /**
     * Merge configuration.
     *
     * @param array $configs
     *   Group of configuration.
     *
     * @return mixed
     *   Merged configuration.
     */
    protected static function merge(array $configs)
    {
        $objects = array_filter($configs, 'is_object');

        if (!empty($objects)) {
            $listConfigs = [];

            foreach ($configs as $config) {
                if (!is_object($config)) {
                    throw new \RuntimeException('Cannot merge object with other types');
                }

                $listConfigs[] = (array) $config;
            }

            $result = (object) static::merge($listConfigs);
        } else {
            foreach ($configs as $config) {
                foreach ($config as $key => $value) {
                    $existed = isset($result[$key]);

                    switch (true) {
                        case ($existed && (is_object($result[$key]) || is_object($value))):
                        case ($existed && (is_array($result[$key]) && is_array($value))):
                            $result[$key] = static::merge(array($result[$key], $value));
                            break;

                        default:
                            $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }
}
