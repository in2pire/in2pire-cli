<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Task;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use In2pire\Cli\Configuration;
use In2pire\Component\Utility\NestedArray;
use In2pire\Cli\Task\Container as TaskContainer;

abstract class CliTask
{
    /**
     * Task ID.
     *
     * @var string
     */
    protected $id = 'default';

    /**
     * Running command.
     *
     * @var In2pire\Cli\Command\CliCommand
     */
    protected $command;

    /**
     * Settings.
     *
     * @var mixed
     */
    protected $settings;

    /**
     * Task data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Return code.
     *
     * @var integer
     */
    protected $returnCode = 0;

    /**
     * Dependency Tasks.
     * @var array
     */
    protected $dependencies = [];

    /**
     * Constructor.
     *
     * @param In2pire\Cli\Command\CliCommand $command
     *   Running command.
     */
    public function __construct($command)
    {
        $this->command = $command;
        $this->settings = Configuration::getAll('cli.task.' . $this->id, true);

        if (!empty($this->settings['dependencies']) && is_array($this->settings['dependencies'])) {
            $dependencies = $this->settings['dependencies'];
            $this->dependencies = array();

            foreach ($dependencies as $dependency) {
                if (is_array($dependency)) {
                    $this->dependencies[key($dependency)] = reset($dependency);
                } else {
                    $this->dependencies[$dependency] = array('mapping' => array());
                }
            }
        }
    }

    /**
     * Get Task ID.
     *
     * @return string
     *   ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get running command.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   Command.
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get all settings.
     *
     * @return array
     *   Settings.
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Get setting.
     *
     * @param string $name
     *   Setting name.
     * @param mixed $default
     *   (optional) Default value when setting is not in file.
     *
     * @return mixed
     *   Setting value.
     */
    public function getSetting($name, $default = null)
    {
        return array_key_exists($name, $this->settings) ? $this->settings[$name] : $default;
    }

    /**
     * Set Data for running.
     */
    public function setData($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = $name;
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * Is successful?
     *
     * @return boolean
     *   Return code.
     */
    public function isSuccessful()
    {
        return $this->returnCode == 0;
    }

    protected function runDependency($task, $taskInfo, InputInterface $input, OutputInterface $output)
    {
        $data = $this->data;

        if (!empty($taskInfo['mapping'])) {
            foreach ($taskInfo['mapping'] as $newKey => $oldKey) {
                $data[$newKey] = NestedArray::getValueByNamespace($data, $oldKey);
            }
        }

        $resturn = $task->setData($data)->run($input, $output);
        return $task->isSuccessful();
    }

    protected function executeDependencies(InputInterface $input, OutputInterface $output)
    {
        $return = 1;

        foreach ($this->dependencies as $taskId => $taskInfo) {
            $task = TaskContainer::create($taskId, $this->command);

            // Failed to execute task.
            if ($task && !$this->runDependency($task, $taskInfo, $input, $output)) {
                $return = 0;
                break;
            }
        }

        return $return;
    }

    /**
     * Do post-run execution.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param int $code
     *   Return code.
     *
     * @return In2pire\Cli\Task\CliTask
     *   The called object.
     */
    protected function doPreRun(InputInterface $input, OutputInterface $output)
    {
        // Do nothing
        return $this;
    }

    /**
     * Do post-run execution.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param int $code
     *   Return code.
     *
     * @return In2pire\Cli\Task\CliTask
     *   The called object.
     */
    protected function doPostRun(InputInterface $input, OutputInterface $output, $code)
    {
        // Do nothing
        return $this;
    }

    /**
     * Execute task.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return int $code
     *   Return code.
     */
    abstract protected function execute(InputInterface $input, OutputInterface $output);

    /**
     * Run task.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return int $code
     *   Return code.
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->returnCode = 0;
        $this->doPreRun($input, $output);

        if (!$this->executeDependencies($input, $output)) {
            // Failed to run dependency.
            $this->returnCode = 1;
        } else {
            // Execute task.
            $this->returnCode = (int) $this->execute($input, $output);
        }

        $this->doPostRun($input, $output, $this->returnCode);

        return $this->returnCode;
    }
}
