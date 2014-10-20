<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Render\CliNcurses;

use Bugzorcist\VarDump\VarDumpCliNcurses;

class NcursesConfigApplication extends NcursesAbstract
{
    /**
     * @var mixed
     */
    private $applicationConfig;

    /**
     * Constructor
     * @param mixed $applicationConfig application config
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($applicationConfig, $padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->applicationConfig = $applicationConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function isShowable()
    {
        return null !== $this->applicationConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $this->printText("Press Enter to show application config");
    }

    /**
     * {@inheritdoc}
     */
    public function onKeyPress($keyCode)
    {
        switch ($keyCode) {
            // enter
            case 13:
                VarDumpCliNcurses::dump($this->applicationConfig, "Application Config", false);
                return true;
                break;
        }

        return false;
    }
}
