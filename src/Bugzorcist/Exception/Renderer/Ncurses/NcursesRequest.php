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

class NcursesRequest extends NcursesVerticalCursorAbstract
{
    /**
     * Var list
     * @var array
     */
    private $varList;

    /**
     * Link each var to its Y position
     * @var array
     */
    private $varPosList = array();

    /**
     * Constructor
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($padPositionX, $padPositionY)
    {
        parent::__construct($padPositionX, $padPositionY);

        $this->varList = array(
            '$_SERVER'  => $_SERVER,
            '$_ENV'     => $_ENV,
        );

        foreach ($this->varList as $name => $var) {
            $this->varPosList[] = $name;
        }
    }

    /**
     * Key press event handler
     * @param int $keyCode code of the pressed key
     * @return boolean true if a resize is needed, false otherwise
     */
    public function onKeyPress($keyCode)
    {
        switch ($keyCode) {
            // enter key
            case 13:
                $name   = $this->varPosList[$this->getCursorPositionY()];
                $var    = $this->varList[$name];
                VarDumpCliNcurses::dump($var, $name, false);
                return true;
                break;

            default:
                parent::onKeyPress($keyCode);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        foreach ($this->varList as $name => $var) {
            ncurses_wattron($this->getPadResource(), NCURSES_A_BOLD);
            $this->printText("<<4>>$name ▸\n");
            ncurses_wattroff($this->getPadResource(), NCURSES_A_BOLD);
        }
    }
}
