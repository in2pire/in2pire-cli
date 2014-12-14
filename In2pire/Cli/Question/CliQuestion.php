<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Question;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cli Question.
 */
abstract class CliQuestion
{
    /**
     * The running command.
     *
     * @var In2pire\Cli\Command\CliCommand
     */
    protected $command = null;

    /**
     * The question helper.
     *
     * @var Symfony\Component\Console\Helper\QuestionHelper
     */
    protected $helper = null;

    /**
     * Constructor.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The running command.
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * Get running command.
     *
     * @return In2pire\Cli\Command\CliCommand
     *   The running command.
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get question helper.
     *
     * @return Symfony\Component\Console\Helper\QuestionHelper
     *   Question helper.
     */
    public function getHelper()
    {
        if ($this->helper === null) {
            $this->helper = $this->command->getHelper('question');
        }

        return $this->helper;
    }

    /**
     * Ask.
     *
     * @param InputInterface $input
     *   Data input.
     * @param OutputInterface $output
     *   Data output.
     */
    abstract public function ask(InputInterface $input, OutputInterface $output);
}
