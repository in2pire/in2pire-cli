<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Configuration;

interface ConfigurationInterface
{
    /**
     * Get configuration.
     *
     * @return array
     *   Configuration.
     */
    public function get();
}
