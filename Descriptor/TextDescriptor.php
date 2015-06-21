<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Descriptor;

use Symfony\Component\Console\Descriptor\TextDescriptor as BaseTextDescriptor;
use Symfony\Component\Console\Input\InputOption as BaseInputOption;

class TextDescriptor extends BaseTextDescriptor
{
    /**
     * {@inheritdoc}
     */
    protected function writeText($content, array $options = [])
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            isset($options['raw_output']) ? !$options['raw_output'] : true
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function formatDefaultValue($default)
    {
        if (PHP_VERSION_ID < 50400) {
            return str_replace('\/', '/', json_encode($default));
        }

        return json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    protected function calculateTotalWidthForOptions($options)
    {
        $totalWidth = 0;
        foreach ($options as $option) {
            $nameLength = 4 + strlen($option->getName()) + 2; // - + shortcut + , + whitespace + name + --

            if ($option->acceptValue()) {
                $valueLength = 1 + strlen($option->getName()); // = + value
                $valueLength += $option->isValueOptional() ? 2 : 0; // [ + ]

                $nameLength += $valueLength;
            }
            $totalWidth = max($totalWidth, $nameLength);
        }

        return $totalWidth;
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(BaseInputOption $option, array $options = [])
    {
        if ($option->acceptValue() && null !== $option->getDefault() && (!is_array($option->getDefault()) || count($option->getDefault()))) {
            $default = sprintf('<comment> [default: %s]</comment>', $this->formatDefaultValue($option->getDefault()));
        } else {
            $default = '';
        }

        $value = '';
        if (!(method_exists($option, 'isFlag') && $option->isFlag()) && $option->acceptValue()) {
            $value = '="..."';

            if ($option->isValueOptional()) {
                $value = '[' . $value . ']';
            }
        }

        $totalWidth = isset($options['total_width']) ? $options['total_width'] : $this->calculateTotalWidthForOptions(array($option));
        $synopsis = sprintf('%s%s',
            $option->getShortcut() ? sprintf('-%s, ', $option->getShortcut()) : '    ',
            sprintf('--%s%s', $option->getName(), $value)
        );

        $spacingWidth = $totalWidth - strlen($synopsis) + 2;

        $this->writeText(sprintf("  <info>%s</info>%s%s%s%s",
            $synopsis,
            str_repeat(' ', $spacingWidth),
            // + 17 = 2 spaces + <info> + </info> + 2 spaces
            preg_replace('/\s*\R\s*/', "\n" . str_repeat(' ', $totalWidth + 17), $option->getDescription()),
            $default,
            $option->isArray() ? '<comment> (multiple values allowed)</comment>' : ''
        ), $options);
    }
}
