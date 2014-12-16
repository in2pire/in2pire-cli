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

        // Parse configuration and return.
        return $configurations[$namespace] = Yaml::parse($file);
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
}
