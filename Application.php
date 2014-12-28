<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

class Application extends \Symfony\Component\Console\Application
{
    /**
     * Description.
     *
     * @var string
     */
    protected $description = null;

    /**
     * @inheritdoc
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $description = null)
    {
        parent::__construct($name, $version);
        $this->description = $description;
    }

    /**
     * @inheritdoc
     */
    public function getHelp()
    {
        $help = $this->getLongVersion();

        if (!empty($this->description)) {
            $help .= "\n\n" . $this->description;
        }

        return $help;
    }

    /**
     * Set description.
     *
     * @param string $description
     *   New description.
     *
     * @return \Symfony\Component\Console\Application
     *   The called object.
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     *   Application description.
     */
    public function getDescription()
    {
        return $this->description;
    }
}
