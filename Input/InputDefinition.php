<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Input;

use Symfony\Component\Console\Input\InputDefinition as ConsoleInputDefinition;
use In2pire\Cli\Input\InputOption;

class InputDefinition extends ConsoleInputDefinition
{
    /**
     * @inheritdoc
     */
    public function getSynopsis()
    {
        $elements = array();
        $flags = array();

        foreach ($this->getOptions() as $option) {
            $shortcut = $option->getShortcut() ? sprintf('-%s|', $option->getShortcut()) : '';

            if ($option instanceof InputOption && $option->isFlag()) {
                $flags[] = sprintf('[%s--%s]', $shortcut, $option->getName());
            } else {
                $elements[] = sprintf('[' . ($option->isValueRequired() ? '%s--%s="..."' : ($option->isValueOptional() ? '%s--%s[="..."]' : '%s--%s')) . ']', $shortcut, $option->getName());
            }
        }

        $elements = array_merge($elements, $flags);

        foreach ($this->getArguments() as $argument) {
            $elements[] = sprintf($argument->isRequired() ? '%s' : '[%s]', $argument->getName() . ($argument->isArray() ? '1' : ''));

            if ($argument->isArray()) {
                $elements[] = sprintf('... [%sN]', $argument->getName());
            }
        }

        return implode(' ', $elements);
    }
}
