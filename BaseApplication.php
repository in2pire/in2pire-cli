<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

abstract class BaseApplication implements ApplicationInterface
{
    /**
     * Settings.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Is Application booted?
     *
     * @var boolean
     */
    protected $booted = false;

    /**
     * Get configuration from settings.yml file.
     *
     * @param string $name
     *   Settings name.
     * @param mixed $default
     *   (optional) Default value when setting is not in file.
     *
     * @return mixed
     *   Configuration value.
     */
    public function getConfig($name, $default = null)
    {
        return array_key_exists($name, $this->settings) ? $this->settings[$name] : $default;
    }

    /**
     * Boot Application.
     *
     * @return In2pire\Cli\ApplicationInterface
     *   The called object.
     */
    public function boot()
    {
        if ($this->booted) {
            return $this;
        }

        // If we have pinba, disable it.
        if (extension_loaded('pinba')) {
            ini_set('pinba.enabled', false);
        }

        // If we have newrelic, disable it.
        if (extension_loaded('newrelic')) {
            ini_set('newrelic.enabled', false);
        }

        // Set timezone.
        if (!empty($this->settings['timezone'])) {
            date_default_timezone_set($this->settings['timezone']);
        }

        $this->booted = true;

        return $this;
    }

    /**
     * Run application.
     *
     * @return In2pire\Cli\ApplicationInterface
     *   The called object.
     */
    public function run()
    {
        if (false === $this->boot()) {
            throw new \RuntimeExeption('Cannot not boot application');
        }

        return $this;
    }
}
