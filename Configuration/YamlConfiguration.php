<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Configuration;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use In2pire\Cli\Configuration\Exception\ParseException;

class YamlConfiguration extends FileConfiguration
{
    /**
     * Constructor
     *
     * @param string $file
     *   Path to configuration file.
     *
     * @throws In2pire\Cli\Configuration\Exception\ParseException
     *   If the YAML is not valid.
     */
    public function __construct($file)
    {
        parent::__construct($file);

        // Parse configuration.
        try {
            $this->configuration = Yaml::parse($file);
        } catch (YamlParseException $e) {
            throw new ParseException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
