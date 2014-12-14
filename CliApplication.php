<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArgvInput as ConsoleInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use In2pire\Cli\Command\Container as CommandContainer;

class CliApplication extends BaseApplication
{
    /**
     * Application name.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Version.
     *
     * @var string
     */
    protected $version = null;

    /**
     * CLI input.
     * @var Symfony\Component\Console\Input\ArgvInput
     */
    protected $request = null;

    /**
     * CLI output
     * @var Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $response = null;

    /**
     * Application runner.
     *
     * @var string
     */
    protected $runner = null;

    /**
     * List of commands defined in settings file.
     *
     * @var array
     */
    protected $commands = [];

    public function __construct()
    {
        // Read settings file.
        $this->settings = Configuration::getAll('application.cli', true);

        // Set application name.
        if (isset($this->settings['name'])) {
            $this->name = $this->settings['name'];
        }

        // Read application version.
        if (isset($this->settings['version'])) {
            $this->version = $this->settings['version'];
        }

        // Prepare symfony cli application.
        $this->runner = new ConsoleApplication($this->name, $this->version);
        $this->request = new ConsoleInput();
        $this->response = new ConsoleOutput();

        if (!empty($this->settings['commands']) && is_array($this->settings['commands'])) {
            $this->commands = $this->settings['commands'];
        }

        foreach ($this->commands as $commandName) {
            if ($command = CommandContainer::create($commandName, $this)) {
                $command->setApplication($this->runner);
                $this->runner->add($command);
                $this->commands[$commandName] = $command;
            }
        }
    }

    /**
     * Get Application settings.
     *
     * @return array
     *   List of settings.
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set a settings.
     *
     * @param string $name
     *   Setting name.
     * @param mixed $default
     *   (optional) Default value if cannot find setting.
     *
     * @return mixed
     *   Setting.
     */
    public function getSetting($name, $default = null)
    {
        return empty($this->settings[$name]) ? $default : $this->settings[$name];
    }

    /**
     * Get application name.
     *
     * @return string
     *   Name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get application version.
     *
     * @return string
     *   Version.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Boot application.
     *
     * @return In2pire\Cli\ApplicationInterface.
     *   The called object.
     */
    public function boot()
    {
        if ($this->booted) {
            // Already booted.
            return $this;
        }

        // Boot parent.
        parent::boot();

        return $this;
    }

    /**
     * Run application.
     *
     * @return In2pire\Cli\ApplicationInterface.
     *   The called object.
     */
    public function run()
    {
        // Boot the application.
        if (false === $this->boot()) {
            exit(1);
        }

        // Let symfony/console do the rest.
        $this->runner->run($this->request, $this->response);
        return $this;
    }
}
