<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Input;

use Symfony\Component\Console\Input\InputOption as ConsoleInputOption;

class InputOption extends ConsoleInputOption
{
    /**
     * If value is a flag.
     */
    const VALUE_FLAG = 16;

    /**
     * @inheritdoc
     */
    protected $mode;

    /**
     * Possible values.
     *
     * @var array
     */
    protected $possibleValues = null;

    /**
     * @inheritdoc
     */
    public function __construct($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        // If mode is flag.
        if ($mode & static::VALUE_FLAG) {
            $this->mode = $mode;
            // Set mode to optional.
            $mode = static::VALUE_OPTIONAL;
            // Force default value is null.
            $default = null;
        }

        parent::__construct($name, $shortcut, $mode, $description, $default);
    }

    /**
     * Returns true if the option is a flag.
     *
     * @return bool
     *   True if mode is self::VALUE_FLAG, false otherwise.
     */
    public function isFlag()
    {
        return static::VALUE_FLAG === (static::VALUE_FLAG & $this->mode);
    }

    /**
     * Get possible values.
     *
     * @return array
     *   Possible values.
     */
    public function getPossibleValues()
    {
        return $this->possibleValues;
    }

    /**
     * Set possible values.
     *
     * @param array $values
     *   Possible values.
     *
     * @return \In2pire\Cli\Input\InputOption
     *   The called object.
     */
    public function setPossibleValues($values)
    {
        $this->possibleValues = $values;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function equals(ConsoleInputOption $option)
    {
        return parent::equals($option)
            && $option->isFlag() === $this->isFlag()
            && $option->getPossibleValues() === $this->getPossibleValues();
        ;
    }
}
