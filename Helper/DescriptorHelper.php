<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Helper;

use Symfony\Component\Console\Helper\DescriptorHelper as BaseDescriptorHelper;
use In2pire\Cli\Descriptor\TextDescriptor;

class DescriptorHelper extends BaseDescriptorHelper
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->register('txt', new TextDescriptor());
    }
}
