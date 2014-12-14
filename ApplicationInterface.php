<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

interface ApplicationInterface
{
    /**
     * Boot.
     *
     * @return In2pire\Cli\ApplicationInterface
     *   The called object.
     */
    public function boot();

    /**
     * Run the application.
     *
     * @return In2pire\Cli\ApplicationInterface
     *   The called object.
     */
    public function run();
}
