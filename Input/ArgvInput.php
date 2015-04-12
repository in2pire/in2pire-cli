<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Input;

use Symfony\Component\Console\Input\ArgvInput as ConsoleArgvInput;
use In2pire\Cli\Input\InputOption;

class ArgvInput extends ConsoleArgvInput
{
    /**
     * Returns true if flag is enabled and not empty.
     *
     * @param string $name
     *   Option name.
     *
     * @return boolean
     *   True if the flag is contained in raw parameters and its value is not
     *   empty.
     */
    public function hasFlag($name)
    {
        $option = $this->definition->getOption($name);

        if (!$option->isFlag()) {
            throw new \InvalidArgumentException(sprintf('The "--%s" option is not a flag.', $name));
        }

        return !empty($this->options[$name]);
    }

    /**
     * @inheritdoc
     */
    protected function parse()
    {
        parent::parse();

        // Transform flags' value.
        foreach ($this->definition->getOptions() as $option) {
            if ($option instanceof InputOption && $option->isFlag()) {
                $name = $option->getName();

                if (!array_key_exists($name, $this->options)) {
                    $this->options[$name] = false;
                } elseif (null === $this->options[$name]) {
                    $this->options[$name] = true;
                }
            }
        }
    }
}
