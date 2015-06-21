<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

use In2pire\Cli\Configuration\PhpConfiguration;
use In2pire\Cli\Configuration\YamlConfiguration;
use In2pire\Cli\Configuration\Exception\FileNotFoundException;
use In2pire\Cli\Configuration\Exception\RuntimeException;
use In2pire\Component\Utility\NestedArray;

/**
 * Configuration.
 */
final class Configuration
{
    /**
     * Path to configuration folder.
     *
     * @var string
     */
    protected $confPath = '.';

    /**
     * Cached configuration.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Get configuration instance.
     *
     * @param string $id
     *   Instance ID.
     *
     * @return In2pire\Cli\Configuration
     *   Configuration.
     */
    public static function getInstance($id = 'application')
    {
        static $instances = [];

        if (isset($instances[$id])) {
            return $instances[$id];
        }

        return $instances[$id] = new static();
    }

    /**
     * Init configuration.
     *
     * @param string $confPath
     *   Path to configuration folder.
     * @param boolean $useCache
     *   Check wether use cached configuration.
     */
    public function init($confPath, $useCache = true)
    {
        $this->confPath = $confPath;

        if ($useCache) {
            $cachedConfig = new PhpConfiguration();
            $this->cache = $cachedConfig->get();
            unset($cachedConfig);
        }
    }

    /**
     * Set path to configuration folder.
     *
     * @param string $path
     *   Path.
     */
    protected function setConfigPath($path)
    {
        $this->confPath = $path;
    }

    /**
     * Get path to configuration folder.
     *
     * @return string
     *   Path.
     */
    protected function getConfigPath()
    {
        return $this->confPath;
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
    protected function getConfigFile($namespace)
    {
        if (empty($namespace)) {
            throw new RuntimeException('Namespace is empty');
        }

        return $this->getConfigPath() . '/' . str_replace('.', '/', $namespace) . '.yml';
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
    protected function load($namespace, $require = false)
    {
        // If cache is set.
        if (isset($this->cache[$namespace])) {
            return $this->cache[$namespace];
        }

        $file = $this->getConfigFile($namespace);
        $configuration = null;

        try {
            $configFile = new YamlConfiguration($file);
            $configuration = $configFile->get();

            if (is_array($configuration) && !empty($configuration['inherits'])) {
                $allConfiguration = [];

                foreach ($configuration['inherits'] as $parentNamespace) {
                    $allConfiguration[] = $this->load($parentNamespace);
                }

                $allConfiguration[] = $configuration;
                $configuration = $this->merge($allConfiguration);
                unset($allConfiguration, $configuration['inherits']);
            }

            unset($configFile);
        } catch (FileNotFoundException $e) {
            if ($require) {
                throw new FileNotFoundException('Could not find settings file for ' . $namespace);
            }
        }

        return $this->cache[$namespace] = $configuration;
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
     */
    public function getAll($namespace, $require = false)
    {
        return $this->load($namespace, $require);
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
     */
    public function get($namespace, $name, $default = null, $require = false)
    {
        $configuration = $this->load($namespace, $require);
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
    protected function merge(array $configs)
    {
        $objects = array_filter($configs, 'is_object');

        if (!empty($objects)) {
            $listConfigs = [];

            foreach ($configs as $config) {
                if (!is_object($config)) {
                    throw new RuntimeException('Cannot merge object with other types');
                }

                $listConfigs[] = (array) $config;
            }

            $result = (object) $this->merge($listConfigs);
        } else {
            foreach ($configs as $config) {
                foreach ($config as $key => $value) {
                    $existed = isset($result[$key]);

                    switch (true) {
                        case ($existed && (is_object($result[$key]) || is_object($value))):
                        case ($existed && (is_array($result[$key]) && is_array($value))):
                            $result[$key] = $this->merge(array($result[$key], $value));
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
