<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer\Ncurses;

/**
 * Ncurses viewers with vertical selector common functionnalities
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
abstract class NcursesVerticalCursorAbstract extends NcursesAbstract
{
    /**
     * Indicates, for each expandable element, if it is expanded
     * @var array
     */
    private $expandableElementList = array();

    /**
     * Link each expandable element to its Y position
     * @var array
     */
    private $expandablePosList = array();

    /**
     * Indicates if all expandable elements must be expanded
     * @var boolean
     */
    private $forceExpandAll = false;

    /**
     * Cursor Y position
     * @var int
     */
    private $cursorPositionY = 0;

    /**
     * Y position of the last printed line
     * @var int
     */
    private $maxY = 0;

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
                // expand/collapse element
                if (array_key_exists($this->cursorPositionY, $this->expandablePosList)) {
                    $idProfiler = $this->expandablePosList[$this->cursorPositionY];
                    $this->expandableElementList[$idProfiler] = !$this->expandableElementList[$idProfiler];
                }
                break;

            // up arrow
            case NCURSES_KEY_UP:
                $this->cursorPositionY = max(0, $this->cursorPositionY - 1);

                if ($this->cursorPositionY < $this->getDecY()) {
                    $decY = $this->cursorPositionY;
                    $this->setDecY($decY);
                }
                break;

            // down arrow
            case NCURSES_KEY_DOWN:
                $this->cursorPositionY = min($this->maxY, $this->cursorPositionY + 1);

                if ($this->cursorPositionY > $this->getPadHeight() + $this->getDecY() - 1) {
                    $decY = $this->cursorPositionY - ($this->getPadHeight() - 1);
                    $this->setDecY($decY);
                }
                break;

            // page up
            case NCURSES_KEY_PPAGE:
                $this->cursorPositionY = max(0, $this->cursorPositionY - $this->getPadHeight());

                if ($this->cursorPositionY < $this->getDecY()) {
                    $decY = $this->cursorPositionY;
                    $this->setDecY($decY);
                }
                break;

            // page down
            case NCURSES_KEY_NPAGE:
                $this->cursorPositionY = min($this->maxY, $this->cursorPositionY + $this->getPadHeight());

                if ($this->cursorPositionY > $this->getPadHeight() + $this->getDecY() - 1) {
                    $decY = min($this->cursorPositionY, $this->maxY - $this->getPadHeight() + 1);
                    $this->setDecY($decY);
                }
                break;

            // right arrow
            // left arrow
            // end key
            // home key
            // ctrl + right arrow
            // ctrl + left arrow
            case NCURSES_KEY_END:
            case NCURSES_KEY_RIGHT:
            case NCURSES_KEY_LEFT:
            case NCURSES_KEY_HOME:
            case 555:
            case 540:
                parent::onKeyPress($keyCode);
                break;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function calculatePadRealSize()
    {
        $this->forceExpandAll = true;
        parent::calculatePadRealSize();
        $this->forceExpandAll = false;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        $this->expandablePosList    = array();
        $this->maxY                 = 0;
        parent::refresh();
    }

    /**
     * {@inheritdoc}
     */
    protected function printRawText($text, $color = VarDumpCliNcurses::COLOR_DEFAULT)
    {
        // if the current position is pointed by the cursor, change the text color to display a white background
        $x = null;
        $y = null;
        ncurses_getyx($this->getPadResource(), $y, $x);

        if ($y == $this->cursorPositionY) {
            $color += 10;
        }

        // print text
        parent::printRawText($text, $color);

        // calculate max Y position
        ncurses_getyx($this->getPadResource(), $y, $x);

        if ("\n" == substr($text, -1)) {
            $y -= 1;
        }

        $this->maxY = max($this->maxY, $y);
    }

    /**
     * Adds an expandable element
     * @param string|number $identifier element identifier
     * @param boolean $isExpanded [optional] indicates if the element is initially expanded. Defaults to false.
     * @throws \InvalidArgumentException
     */
    protected function addExpandableElement($identifier, $isExpanded = false)
    {
        if (!is_string($identifier) && !is_numeric($identifier)) {
            throw new \InvalidArgumentException("Invalid element identifier. Must be a string or a number");
        }

        $this->expandableElementList[$identifier] = $isExpanded;
    }

    /**
     * Sets the Y position of an element
     * @param string|number $identifier element identifier
     * @param int $posY y position
     */
    protected function setExpandableElementPositionY($identifier, $posY)
    {
        $this->expandablePosList[$posY] = $identifier;
    }

    /**
     * Indicates if an element is expanded
     * @param string|number $identifier element identifier
     * @return boolean
     */
    protected function isElementExpanded($identifier)
    {
        return (array_key_exists($identifier, $this->expandableElementList) && $this->expandableElementList[$identifier]) || $this->forceExpandAll;
    }

    /**
     * Returns the cursor Y position
     * @return int
     */
    protected function getCursorPositionY()
    {
        return $this->cursorPositionY;
    }
}
