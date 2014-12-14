<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Validator;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Cli Validator.
 */
abstract class CliValidator
{
    /**
     * The running command.
     *
     * @var In2pire\Cli\Command\CliCommand
     */
    protected $command = null;

    /**
     * Constructor
     *
     * @param In2pire\Cli\Command\CliCommand $command
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
     * Validate data input
     *
     * @param  Symfony\Component\Console\Input\InputInterface $input
     *   Data input.
     *
     * @return array
     *   List of validated data.
     */
    abstract public function validate(InputInterface $input);
}
