<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Configuration;

use In2pire\Cli\Configuration\Exception\FileNotFoundException;

class FileConfiguration implements ConfigurationInterface
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $configuration = null;

    /**
     * Constructor
     *
     * @param string $file
     *   Path to configuration file.
     *
     * @throws In2pire\Cli\Configuration\Exception\FileNotFoundException
     *   If file does not exist
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException('Could not find configuration file ' . $file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->configuration;
    }
}
