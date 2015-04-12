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
}
