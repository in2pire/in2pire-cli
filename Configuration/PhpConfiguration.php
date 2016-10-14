<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Configuration;

class PhpConfiguration implements ConfigurationInterface
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // [CONFIGURATION]
    }

    /**
     * Get cached configuration.
     *
     * @return array
     *   Configuration.
     */
    public function get()
    {
        return $this->configuration;
    }
}
