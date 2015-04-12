<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Command;

use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputArgument as ConsoleInputArgument;
use In2pire\Cli\Configuration;
use In2pire\Cli\Input\InputDefinition;
use In2pire\Cli\Input\InputOption as ConsoleInputOption;
use In2pire\Cli\Token;
use In2pire\Cli\Task\Container as TaskContainer;
use In2pire\Cli\Question\Container as QuestionContainer;
use In2pire\Cli\Validator\Container as ValidatorContainer;
use In2pire\Component\Utility\NestedArray;

/**
 * Base Cli Command.
 */
abstract class CliCommand extends ConsoleCommand
{
    /**
     * Error code.
     */
    const RETURN_ERROR = 1;

    /**
     * Success code.
     */
    const RETURN_SUCCESS = 0;

    /**
     * Command name.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * Running Application.
     *
     * @var In2pire\Cli\CliApplication
     */
    protected $app = null;

    /**
     * Command validators.
     *
     * @var array
     */
    protected $validators = [];

    /**
     * Command questions.
     *
     * @var array
     */
    protected $questions = [];

    /**
     * List of task to be run when command is executed.
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * Return code.
     *
     * @var integer
     */
    protected $returnCode = 0;

    /**
     * Constructor.
     *
     * @param In2pire\Cli\CliApplication
     *   Cli Application.
     */
    public function __construct($app = null)
    {
        // Contruct symfony command.
        parent::__construct($this->name);

        // Set new definition.
        $this->setDefinition(new InputDefinition());

        // Set status
        $this->returnCode = static::RETURN_SUCCESS;

        // Set running application.
        $this->app = $app;
        // Get setting.
        $this->settings = Configuration::getAll('command.' . $this->name, true);

        // Prepare command information.
        if (!empty($this->settings['description'])) {
            $this->setDescription($this->settings['description']);
        }

        if (!empty($this->settings['help'])) {
            $this->setHelp($this->settings['help']);
        }

        if (!empty($this->settings['tasks']) && is_array($this->settings['tasks'])) {
            $this->tasks = $this->settings['tasks'];
        }

        if (!empty($this->settings['questions']) && is_array($this->settings['questions'])) {
            foreach ($this->settings['questions'] as $question) {
                if ($question = QuestionContainer::create($question, $this)) {
                    $this->questions[] = $question;
                }
            }
        }

        if (!empty($this->settings['validators']) && is_array($this->settings['validators'])) {
            foreach ($this->settings['validators'] as $validator) {
                if ($validator = ValidatorContainer::create($validator, $this)) {
                    $this->validators[] = $validator;
                }
            }
        }

        $definitions = [];

        if (!empty($this->settings['arguments']) && is_array($this->settings['arguments'])) {
            foreach ($this->settings['arguments'] as $definition) {
                if (!empty($definition['name'])) {
                    $name        = $definition['name'];
                    $mode        = empty($definition['mode']) ? null : (int) $definition['mode'];
                    $description = empty($definition['description']) ? '' : Token::replace($definition['description']);
                    $default     = empty($definition['default']) ? null : Token::replace($definition['default']);

                    $definitions[] = new ConsoleInputArgument($name, $mode, $description, $default);
                }
            }
        }

        if (!empty($this->settings['options']) && is_array($this->settings['options'])) {
            foreach ($this->settings['options'] as $definition) {
                if (!empty($definition['name'])) {
                    $name        = $definition['name'];
                    $shortcut    = empty($definition['shortcut']) ? null : $definition['shortcut'];
                    $mode        = empty($definition['mode']) ? null : (int) $definition['mode'];
                    $description = empty($definition['description']) ? '' : Token::replace($definition['description']);
                    $default     = empty($definition['default']) ? null : Token::replace($definition['default']);

                    $definitions[] = new ConsoleInputOption($name, $shortcut, $mode, $description, $default);
                }
            }
        }

        if (!empty($definitions)) {
            $this->setDefinition($definitions);
        }
    }

    /**
     * Prepare data before execution.
     *
     * @param InputInterface  $input
     *   Input.
     *
     * @return array
     *   Prepared data.
     */
    protected function prepareData(InputInterface $input)
    {
        $return = [];

        foreach ($this->validators as $validator) {
            $result = $validator->validate($input);

            if (isset($result) && is_array($result)) {
                $return = NestedArray::mergeDeep($return, $result);
            } elseif (isset($result)) {
                $return[] = $result;
            }
        }

        return $return;
    }

    /**
     * Is successful?
     *
     * @return boolean
     *   Return code.
     */
    public function isSuccessful()
    {
        return $this->returnCode == static::RETURN_SUCCESS;
    }

    /**
     * Do pre-run execution.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPreRun(InputInterface $input, OutputInterface $output)
    {
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
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPostRun(InputInterface $input, OutputInterface $output, $code = 0)
    {
        return $this;
    }

    /**
     * Run command.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return int
     *   Return code.
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->doPreRun($input, $output);
        $code = parent::run($input, $output);
        $this->doPostRun($input, $output, $code);

        return $code;
    }

    /**
     * Do pre execute.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPreExecute(InputInterface $input, OutputInterface $output)
    {
        return $this;
    }

    /**
     * Do post execute.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPostExecute(InputInterface $input, OutputInterface $output)
    {
        return $this;
    }

    /**
     * Do pre execute tasks.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPreExecuteTasks(InputInterface $input, OutputInterface $output, $data)
    {
        return $this;
    }

    /**
     * Do post execute tasks.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPostExecuteTasks(InputInterface $input, OutputInterface $output, $data)
    {
        return $this;
    }

    /**
     * Do pre execute task.
     *
     * @param In2pire\Cli\Task\BaseTask
     *   Task.
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPreExecuteTask($task, InputInterface $input, OutputInterface $output, $data)
    {
        return $this;
    }

    /**
     * Do post execute task.
     *
     * @param In2pire\Cli\Task\BaseTask
     *   Task.
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     * @param int $code
     *   Return code.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The called object.
     */
    protected function doPostExecuteTask($task, InputInterface $input, OutputInterface $output, $data, $code)
    {
        return $this;
    }

    /**
     * Execute tasks.
     *
     * @param In2pire\Cli\Task\BaseTask
     *   Task.
     * @param InputInterface $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     *
     * @return boolean
     *   True or False.
     */
    protected function executeTask($task, InputInterface $input, OutputInterface $output, $data)
    {
        $this->doPreExecuteTask($task, $input, $output, $data);
        $return = $task->setData($data)->run($input, $output);
        $this->doPostExecuteTask($task, $input, $output, $data, $return);
        return $task->isSuccessful();
    }

    /**
     * Execute tasks.
     *
     * @param InputInterface $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     * @param array $data
     *   Prepared data.
     *
     * @return boolean
     *   True or False.
     */
    protected function executeTasks(InputInterface $input, OutputInterface $output, $data)
    {
        foreach ($this->tasks as $taskId) {
            $task = TaskContainer::create($taskId, $this);

            // Failed to execute task.
            if ($task && !$this->executeTask($task, $input, $output, $data)) {
                return static::RETURN_ERROR;
            }
        }

        return static::RETURN_SUCCESS;
    }

    /**
     * Ask question before executing command.
     *
     * @param InputInterface  $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return boolean
     *   True if passes all questions. Otherwise false.
     */
    protected function askQuestions(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->questions as $question) {
            if (!$question->ask($input, $output)) {
                return static::RETURN_ERROR;
            }
        }

        return static::RETURN_SUCCESS;
    }

    /**
     * Do Execute command.
     *
     * @param InputInterface $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return int
     *   Return Code.
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // Ask questions.
        if ($this->askQuestions($input, $output) == static::RETURN_ERROR) {
            return static::RETURN_ERROR;
        }

        // Prepare data.
        $data = $this->prepareData($input);
        // Pre-execute
        $this->doPreExecuteTasks($input, $output, $data);
        // Execute.
        $return = $this->executeTasks($input, $output, $data);
        // Post execute.
        $this->doPostExecuteTasks($input, $output, $data, $return);

        return $return;
    }

    /**
     * Execute command.
     *
     * @param InputInterface $input
     *   Input.
     * @param OutputInterface $output
     *   Output.
     *
     * @return int
     *   Return Code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->doPreExecute($input, $output);
            $this->returnCode = $this->doExecute($input, $output);
            $this->doPostExecute($input, $output);
        } catch(Exception $e) {
            $this->returnCode = static::RETURN_ERROR;
            throw $e;
        }

        return $this->returnCode;
    }
}
