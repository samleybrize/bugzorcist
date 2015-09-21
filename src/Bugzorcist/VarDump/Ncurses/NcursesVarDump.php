<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump\Ncurses;

use Bugzorcist\VarDump\VarDumpNcurses;
use Bugzorcist\VarDump\VarTree;
use Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract;

/**
 * Ncurses var dump viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDump implements NcursesInterface
{
    const COLOR_REF_OBJECT_SRC  = 26;
    const COLOR_REF_OBJECT_DST  = 27;
    const COLOR_SEARCH_MATCH    = 28;

    /**
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     */
    private $var;

    /**
     * Ncurses pad
     * @var resource
     */
    private $pad;

    /**
     * Ncurses search box pad
     * @var resource
     */
    private $padSearch;

    /**
     * Width of the viewport
     * @var int
     */
    private $padWidth;

    /**
     * Height of the viewport
     * @var int
     */
    private $padHeight;

    /**
     * Real width of the pad
     * @var int
     */
    private $padRealWidth;

    /**
     * Real height of the pad
     * @var int
     */
    private $padRealHeight;

    /**
     * X position of the pad in the main screen
     * @var int
     */
    private $padPositionY;

    /**
     * Y position of the pad in the main screen
     * @var int
     */
    private $padPositionX;

    /**
     * Current X position
     * @var int
     */
    private $posX = 0;

    /**
     * Current Y position
     * @var int
     */
    private $posY = 0;

    /**
     * Y position of the most lower line
     * @var int
     */
    private $maxY = 0;

    /**
     * Left position of the viewport in the pad
     * @var int
     */
    private $decX = 0;

    /**
     * Top position of the viewport in the pad
     * @var int
     */
    private $decY = 0;

    /**
     * Y position of the cursor
     * @var int
     */
    private $cursorPositionY = 0;

    /**
     * Y position of the highlighted line
     * @var int
     */
    private $highlightedPositionY = 0;

    /**
     * Indicates if highlighting is enabled
     * @var boolean
     */
    private $cursorHighlight = true;

    /**
     * List of expandable elements
     * @var array
     */
    private $expandableList = array();

    /**
     * List of reference Y position of lines (for multiline elements).
     * Eg: if an element is on lines [2, 3, 4], the reference for lines [3, 4] is 2.
     * @var array
     */
    private $highlightRefYList = array();

    /**
     * List of colors that must be displayed as bold text
     * @var array
     */
    private $boldColorList = array(
        VarDumpNcurses::COLOR_BLUE,
        VarDumpNcurses::COLOR_MAGENTA,
    );

    /**
     * Last clicked element that points to another element
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     */
    private $highlightedReferencer;

    /**
     * Last clicked element that is a referenced element
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     */
    private $highlightedReferenced;

    /**
     * Indicates if printing is disabled
     * @var boolean
     */
    private $disablePrint = false;

    /**
     * Text to search
     * @var string
     */
    private $searchText = "";

    /**
     * Number of found search occurences
     * @var string
     */
    private $searchFoundOccurences = 0;

    /**
     * Found search occurences UID list
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract[]
     */
    private $searchFoundList = array();

    /**
     * Whether to show search pad
     * @var boolean
     */
    private $showSearchPad = false;

    /**
     * Indicates if search pad is in edit mode
     * @var boolean
     */
    private $editSearchPad = false;

    /**
     * Constructor
     * @param mixed $var var to dump
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($var, $padPositionX, $padPositionY)
    {
        $varTree                = new VarTree($var);
        $this->var              = NcursesVarDumpTypeAbstract::factory($varTree->getTree());
        $this->padPositionX     = (int) $padPositionX;
        $this->padPositionY     = (int) $padPositionY;
    }

    /**
     * Calculates the real width of the pad
     */
    protected function calculatePadRealSize()
    {
        // when retrieving width, the width of the expanded version is retrieved.
        // string type may have a collapsed version longer than its expanded version
        $this->expandAll($this->var);
        $expandedWidth          = max($this->var->getStringWidth(), $this->var->getChildrenWidth(true));
        $this->var->collapse(true);
        $collapsedWidth         = max($this->var->getStringWidth(), $this->var->getChildrenWidth(true));

        $this->padRealWidth     = max($collapsedWidth, $expandedWidth);
    }

    /**
     * Creates the ncurses pad.
     * If the pad already exists, it is deleted and recreated.
     * @throws \RuntimeException
     */
    protected function createPad()
    {
        if (null !== $this->pad) {
            ncurses_delwin($this->pad);
            ncurses_delwin($this->padSearch);
        } elseif (null === $this->padRealWidth) {
            $this->calculatePadRealSize();
        }

        $globalWidth    = null;
        $globalHeight   = null;
        ncurses_getmaxyx(STDSCR, $globalHeight, $globalWidth);

        $this->padRealHeight    = $globalHeight - 3;
        $this->padWidth         = $globalWidth;
        $this->padHeight        = $this->padRealHeight;
        $w                      = max($this->padWidth, $this->padRealWidth);
        $h                      = max($this->padHeight, $this->padRealHeight);
        $this->pad              = ncurses_newpad($h, $w);
        $this->padSearch        = ncurses_newpad(1, $this->padWidth);

        if (false === $this->pad) {
            throw new \RuntimeException("Failed to create a ncurses pad (width: $w, height: $h)");
        }

        ncurses_keypad($this->pad, true);
    }

    /**
     * {@inheritdoc}
     */
    public function clearPad()
    {
        if (null !== $this->pad) {
            ncurses_delwin($this->pad);
            ncurses_delwin($this->padSearch);
            $this->pad          = null;
            $this->padSearch    = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onKeyPress($keyCode)
    {
        switch ($keyCode) {
            // F5
            case NCURSES_KEY_F5:
                // collapse all
                $this->var->collapse(true);
                $this->gotoPositionY(0);
                break;

            // F6
            case NCURSES_KEY_F6:
                // expand all visible elements
                foreach ($this->expandableList as $expandable) {
                    $expandable->expand();
                }
                break;

            // F7
            case NCURSES_KEY_F7:
                // expand all elements
                $this->expandAll($this->var);
                break;

            // F8
            case NCURSES_KEY_F8:
                // toggle cursor highlight
                $this->cursorHighlight = !$this->cursorHighlight;
                break;

            // F9
            case NCURSES_KEY_F9:
                // search text
                $this->showSearchPad    = true;
                $this->editSearchPad    = true;
                $rawSearchText          = $this->searchText;
                $previousSearchText     = "";
                $this->refreshSearchPad();

                while (true) {
                    $searchKeyCode      = ncurses_wgetch($this->pad);
                    $input              = chr($searchKeyCode);

                    if (27 === $searchKeyCode || (13 === $searchKeyCode && "" === $this->searchText)) {
                        // end search if pressed key is ESC
                        // end also if pressed key is ENTER and search text is empty
                        $this->showSearchPad            = false;
                        $this->editSearchPad            = false;
                        $this->searchText               = "";
                        $this->searchFoundOccurences    = 0;
                        $this->searchFoundList          = array();
                        $this->var->clearSearch();
                        break;
                    } elseif (13 === $searchKeyCode) {
                        // end input if pressed key is ENTER
                        $this->editSearchPad            = false;
                        $this->searchFoundList          = $this->var->searchText($this->searchText);
                        $this->searchFoundOccurences    = count($this->searchFoundList);
                        break;
                    } elseif (NCURSES_KEY_BACKSPACE === $searchKeyCode) {
                        // delete last character
                        $rawSearchText      = $this->cleanString($rawSearchText);
                        $rawSearchText      = mb_substr($rawSearchText, 0, -1, mb_detect_encoding($rawSearchText));
                        $this->searchText   = $rawSearchText;
                        $previousSearchText = $this->searchText;
                        $this->refreshSearchPad();
                    } else {
                        // strip non printable characters (such as arrow keys, ...)
                        $rawSearchText     .= $input;
                        $this->searchText   = $this->cleanString($rawSearchText);

                        // refresh only if the new text is different from the previous
                        if (strlen($previousSearchText) < strlen($this->searchText)) {
                            $this->refreshSearchPad();
                        }

                        $previousSearchText = $this->searchText;
                    }
                }
                break;

            // "n" / "N" key
            case 110:
            case 78:
                // got to next search occurence
                if ($this->showSearchPad && !$this->editSearchPad) {
                    // if no match, do nothing
                    if (empty($this->searchFoundList)) {
                        break;
                    }

                    // iterate through all found occurences
                    // find the first whose Y position is higher than cursor Y position, or the first hidden element
                    foreach ($this->searchFoundList as $var) {
                        $varPosY = $var->getLastPosY();

                        if (null === $varPosY || $varPosY > $this->cursorPositionY) {
                            // expand + expand parents
                            // go to its new Y pos
                            $this->disablePrint = true;
                            $var->expand(true);
                            $this->refresh();
                            $this->disablePrint = false;
                            $this->gotoPositionY($var->getLastPosY());
                            break 2;
                        }
                    }

                    // if none found, go to the first occurence
                    reset($this->searchFoundList);
                    $var                = current($this->searchFoundList);
                    $this->disablePrint = true;
                    $var->expand(true);
                    $this->refresh();
                    $this->disablePrint = false;
                    $this->gotoPositionY($var->getLastPosY());
                }
                break;

            // "p" / "P" key
            case 112:
            case 80:
                // got to previous search occurence
                if ($this->showSearchPad && !$this->editSearchPad) {
                    // if no match, do nothing
                    if (empty($this->searchFoundList)) {
                        break;
                    }

                    // iterate through all found occurences
                    // find the first whose Y position is lower than cursor Y position, or the first hidden element
                    $searchFoundList = array_reverse($this->searchFoundList);

                    foreach ($searchFoundList as $var) {
                        $varPosY = $var->getLastPosY();

                        if (null === $varPosY || $varPosY < $this->cursorPositionY) {
                            // expand + expand parents
                            // go to its new Y pos
                            $this->disablePrint = true;
                            $var->expand(true);
                            $this->refresh();
                            $this->disablePrint = false;
                            $this->gotoPositionY($var->getLastPosY());
                            break 2;
                        }
                    }

                    // if none found, go to the last occurence
                    end($this->searchFoundList);
                    $var                = current($this->searchFoundList);
                    $this->disablePrint = true;
                    $var->expand(true);
                    $this->refresh();
                    $this->disablePrint = false;
                    $this->gotoPositionY($var->getLastPosY());
                }
                break;

            // enter key
            case 13:
                // expand array/object/string
                if (array_key_exists($this->highlightedPositionY, $this->expandableList)) {
                    $element    = $this->expandableList[$this->highlightedPositionY];
                    $refUid     = $element->getRefUid();

                    if ($element->getRefUid()) {
                        // element that refers to another
                        // find referenced element and expand it
                        $refTree = $this->var->findUid($refUid);

                        if (false === $refTree) {
                            return;
                        }

                        $refTree->expand(true);

                        // refresh to update elements positions
                        $this->disablePrint = true;
                        $this->refresh();
                        $this->disablePrint = false;
                        $dstY = $refTree->getLastPosY();

                        // goto to y position
                        if (false !== $dstY) {
                            $this->gotoPositionY($dstY);
                        }

                        // hold elements for highlighting
                        if ($this->highlightedReferencer) {
                            $this->highlightedReferencer->highlightAsReferencer(false);
                            $this->highlightedReferenced->highlightAsReferenced(false);
                        }

                        $element->highlightAsReferencer(true);
                        $refTree->highlightAsReferenced(true);
                        $this->highlightedReferencer = $element;
                        $this->highlightedReferenced = $refTree;
                    } else {
                        // regular element
                        $element->toggleExpand();

                        // if the selected line is not the first line of the element, go up until the first line
                        if ($this->cursorPositionY != $this->highlightedPositionY) {
                            $up = $this->cursorPositionY - $this->highlightedPositionY;

                            for ($i = 0; $i < $up; $i++) {
                                $this->onKeyPress(259);
                            }
                        }
                    }
                }
                break;

            // up arrow
            case NCURSES_KEY_UP:
                do {
                    $newCursorPositionY = max(0, $this->cursorPositionY - 1);

                    if ($newCursorPositionY != $this->cursorPositionY) {
                        $this->cursorPositionY = $newCursorPositionY;
                        $this->updateHighlightedPosition($this->cursorPositionY);

                        // if the cursor is outside the screen, apply a shift in order to move the content
                        if ($newCursorPositionY - $this->decY < 0) {
                            $this->decY--;
                            break;
                        }
                    }

                    // if current position is the min position, break to avoid infinite loop
                    if ($this->cursorPositionY == 0) {
                        break;
                    }
                } while (array_key_exists($this->cursorPositionY, $this->highlightRefYList));
                break;

            // down arrow
            case NCURSES_KEY_DOWN:
                $oldCursorPositionY = $this->cursorPositionY;

                do {
                    $newCursorPositionY = min($this->maxY - 1, $this->cursorPositionY + 1);

                    if ($newCursorPositionY != $this->cursorPositionY && $newCursorPositionY != $this->maxY) {
                        $this->cursorPositionY = $newCursorPositionY;
                        $this->updateHighlightedPosition($this->cursorPositionY);

                        // if the cursor is outside the screen, apply a shift in order to move the content
                        if ($newCursorPositionY - $this->decY >= $this->padHeight - 1) {
                            $this->decY++;
                            break;
                        }
                    }

                    // if current position is the max position, break to avoid infinite loop
                    if ($this->cursorPositionY == $this->maxY - 1) {
                        if (in_array($this->cursorPositionY, $this->highlightRefYList)) {
                            $this->cursorPositionY = $oldCursorPositionY;
                            $this->updateHighlightedPosition($this->cursorPositionY);
                        }

                        break;
                    }
                } while (array_key_exists($this->cursorPositionY, $this->highlightRefYList));
                break;

            // page up
            case NCURSES_KEY_PPAGE:
                do {
                    $newCursorPositionY = max(0, $this->cursorPositionY - $this->padHeight);

                    if ($newCursorPositionY != $this->cursorPositionY) {
                        $this->cursorPositionY = $newCursorPositionY;
                        $this->updateHighlightedPosition($this->cursorPositionY);

                        // if the cursor is outside the screen, apply a shift in order to move the content
                        if ($newCursorPositionY - $this->decY < 0) {
                            $this->decY += $newCursorPositionY - $this->decY;
                            break;
                        }
                    }

                    // if current position is the min position, break to avoid infinite loop
                    if ($this->cursorPositionY == 0) {
                        break;
                    }
                } while (array_key_exists($this->cursorPositionY, $this->highlightRefYList));
                break;

            // page down
            case NCURSES_KEY_NPAGE:
                $oldCursorPositionY = $this->cursorPositionY;

                do {
                    $newCursorPositionY = min($this->maxY - 1, $this->cursorPositionY + $this->padHeight);

                    if ($newCursorPositionY != $this->cursorPositionY) {
                        $this->cursorPositionY = $newCursorPositionY;
                        $this->updateHighlightedPosition($this->cursorPositionY);

                        // if the cursor is outside the screen, apply a shift in order to move the content
                        if ($newCursorPositionY - $this->decY >= $this->padHeight - 1) {
                            $this->decY = min(
                                $newCursorPositionY,
                                $this->maxY - ($this->padHeight - 1)
                            );
                            break;
                        }
                    }

                    // if current position is the max position, break to avoid infinite loop
                    if ($this->cursorPositionY == $this->maxY - 1) {
                        if (in_array($this->cursorPositionY, $this->highlightRefYList)) {
                            $this->cursorPositionY = $oldCursorPositionY;
                            $this->updateHighlightedPosition($this->cursorPositionY);
                        }

                        break;
                    }
                } while (array_key_exists($this->cursorPositionY, $this->highlightRefYList));
                break;

            // right arrow
            case NCURSES_KEY_RIGHT:
                $this->decX = min($this->padRealWidth - $this->padWidth, $this->decX + 1);
                break;

            // left arrow
            case NCURSES_KEY_LEFT:
                $this->decX = max(0, $this->decX - 1);
                break;

            // end key
            case NCURSES_KEY_END:
                $this->decX = $this->padRealWidth - $this->padWidth;
                break;

            // home key
            case NCURSES_KEY_HOME:
                $this->decX = 0;
                break;

            // ctrl + right arrow
            case 555:
            case 559:
                $this->decX = min($this->padRealWidth - $this->padWidth, $this->decX + $this->padWidth);
                break;

            // ctrl + left arrow
            case 540:
            case 544:
                $this->decX = max(0, $this->decX - $this->padWidth);
                break;

            default:
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        // create ncurses pad and define some colors
        if (null === $this->pad) {
            $this->createPad();
            ncurses_init_pair(self::COLOR_REF_OBJECT_SRC, NCURSES_COLOR_BLACK, NCURSES_COLOR_YELLOW);
            ncurses_init_pair(self::COLOR_REF_OBJECT_SRC + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_YELLOW);
            ncurses_init_pair(self::COLOR_REF_OBJECT_DST, NCURSES_COLOR_BLACK, NCURSES_COLOR_RED);
            ncurses_init_pair(self::COLOR_REF_OBJECT_DST + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_RED);
            ncurses_init_pair(self::COLOR_SEARCH_MATCH, NCURSES_COLOR_BLACK, NCURSES_COLOR_CYAN);
            ncurses_init_pair(self::COLOR_SEARCH_MATCH + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_CYAN);
        }

        // clear pad
        if (!$this->disablePrint) {
            ncurses_werase($this->pad);
        }

        // render var
        $this->setPositionXY(0, 0);

        $this->maxY                     = $this->var->getStringHeight() + $this->var->getChildrenHeight();
        $this->expandableList           = array();
        $this->highlightRefYList        = array();

        $locale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, "C");
        $this->renderVar($this->var);
        setlocale(LC_NUMERIC, $locale);

        // refresh ncurses pad
        if (!$this->disablePrint) {
            ncurses_prefresh(
                $this->pad,
                0, $this->decX,
                $this->padPositionY, $this->padPositionX,
                $this->padHeight + $this->padPositionY - 1, $this->padWidth - 1
            );
        }

        $this->refreshSearchPad();
    }

    /**
     * Refresh search pad content
     */
    protected function refreshSearchPad()
    {
        if (!$this->showSearchPad) {
            return;
        }

        $backgroundColor    = $this->editSearchPad ? NCURSES_COLOR_YELLOW : NCURSES_COLOR_MAGENTA;
        $length             = 1;

        if ($this->editSearchPad) {
            // edit mode
            $labels = array(
                "ESC"   => "Cancel",
                "ENTER" => "Done",
            );
        } else {
            // not in edit mode
            $labels = array(
                "F9" => "Edit",
            );
        }

        ncurses_werase($this->padSearch);

        // write an empty space
        ncurses_wattron($this->padSearch, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->padSearch, $backgroundColor);
        ncurses_waddstr($this->padSearch, " ");
        ncurses_wattroff($this->padSearch, NCURSES_A_REVERSE);

        // write key/labels
        foreach ($labels as $key => $label) {
            ncurses_wcolor_set($this->padSearch, 0);
            ncurses_waddstr($this->padSearch, $key);
            ncurses_wattron($this->padSearch, NCURSES_A_REVERSE);
            ncurses_wcolor_set($this->padSearch, $backgroundColor);
            ncurses_waddstr($this->padSearch, "$label ");
            ncurses_wattroff($this->padSearch, NCURSES_A_REVERSE);

            $length += strlen($key);
            $length += strlen($label);
            $length += 1;
        }

        // search box
        $length += 2;
        ncurses_wcolor_set($this->padSearch, 0);
        ncurses_waddstr($this->padSearch, "  ");
        ncurses_wattron($this->padSearch, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->padSearch, $backgroundColor);

        if (!$this->editSearchPad) {
            // show found occurences
            $text       = " [$this->searchFoundOccurences found]";
            $length    += strlen($text);
            ncurses_waddstr($this->padSearch, $text);
        }

        ncurses_waddstr($this->padSearch, str_pad(" Search: $this->searchText", $this->padWidth - $length, " ", STR_PAD_RIGHT));
        ncurses_wattroff($this->padSearch, NCURSES_A_REVERSE);

        // display pad
        ncurses_prefresh(
            $this->padSearch,
            0, 0,
            $this->padHeight + $this->padPositionY - 1, $this->padPositionX,
            $this->padHeight + $this->padPositionY - 1, $this->padWidth - 1
        );
    }

    /**
     * Renders the var
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $var var to render
     * @param int $level [optional] depth level
     */
    protected function renderVar(NcursesVarDumpTypeAbstract $var, $level = 0)
    {
        $var->setLastPosY($this->posY);

        // verify if the entire element (string + children) will be printed outside of the viewport
        $totalHeight = $var->getStringHeight() + $var->getChildrenHeight();

        if ($this->isBeingPrintedOutside($totalHeight)) {
            $this->addPosition(0, $totalHeight);
            return;
        }

        // add var to expandable list
        if ($var->isExpandable()) {
            $this->expandableList[$this->posY] = $var;
        }

        // render string
        $strHeight = $var->getStringHeight();

        if (!$this->isBeingPrintedOutside($strHeight)) {
            $strArray   = $var->getStringArray();
            $color      = null;
            $str        = null;
            $this->printRawText(str_repeat("    ", $level));

            foreach ($strArray as $v) {
                if (null === $color) {
                    $color = $v;
                    continue;
                }

                $str = $v;
                $this->printRawText($str, $color);

                $str    = null;
                $color  = null;

            }

            // in case of a multiline string, lines other than the first are marked as unselectable
            if ($strHeight > 1) {
                for ($i = $this->posY + 1; $i <= $this->posY + ($strHeight - 1); $i++) {
                    $this->highlightRefYList[$i] = $this->posY;
                }
            }
        }

        $this->addPosition(0, $var->getStringHeight());
        $level++;

        // render children
        $children = $var->getChildren();

        foreach ($children as $child) {
            $this->renderVar($child, $level);
        }
    }

    /**
     * Moves position
     * @param int $x [optional] number of characters to add to x. Can be negative. Defaults to 0.
     * @param int $y [optional] number of characters to add to y. Can be negative. Defaults to 0.
     */
    protected function addPosition($x = 0, $y = 0)
    {
        $this->posX += $x;
        $this->posY += $y;
        $this->setPositionXY($this->posX, $this->posY);
    }

    /**
     * Defines X and Y position
     * @param int $x
     * @param int $y
     */
    protected function setPositionXY($x, $y)
    {
        $this->posX = max(0, $x);
        $this->posY = max(0, $y);
        ncurses_wmove($this->pad, $this->posY - $this->decY, $this->posX);

        $this->maxY = max($this->maxY, $this->posY);
    }

    /**
     * Defines X position
     * @param int $x
     */
    protected function setPositionX($x)
    {
        $this->posX = $x;
        $this->setPositionXY($this->posX, $this->posY);
    }

    /**
     * Defines Y position
     * @param int $Y
     */
    protected function setPositionY($y)
    {
        $this->posY = $y;
        $this->setPositionXY($this->posX, $this->posY);
    }

    /**
     * Update the highlighted Y position, based on a Y coordinate
     * @param int $posY
     */
    protected function updateHighlightedPosition($posY)
    {
        $this->highlightedPositionY = array_key_exists($posY, $this->highlightRefYList) ?
            $this->highlightRefYList[$posY] :
            (int) $posY
        ;
    }

    /**
     * Prints a text to the pad
     * @param string $text text to print
     * @param int $color [optional] text color. One of the VarDumpNcurses::COLOR_* constants. Defaults to VarDumpNcurses::COLOR_DEFAULT.
     * @return int number of characters printed
     */
    protected function printRawText($text, $color = VarDumpNcurses::COLOR_DEFAULT)
    {
        if ($this->disablePrint) {
            return;
        }

        // if the line being printed is the one pointed by the cursor, highlight it
        if ($this->posY == $this->highlightedPositionY && $this->cursorHighlight) {
            $color += 10;
        }

        // verify if the color must be bolded
        $bold = false;

        if (in_array($color, $this->boldColorList)) {
            ncurses_wattron($this->pad, NCURSES_A_BOLD);
            $bold = true;
        }

        // print the text line by line
        // each line is checked for its visibility
        // we save the current Y position to be able to restore it
        $lines  = explode("\n", $text);
        $nLines = count($lines);
        $posY   = $this->posY;

        foreach ($lines as $k => $line) {
            if ($this->isBeingPrintedOutside(1)) {
                // the line is outside of the viewport
                // we increment the current Y position and skip drawing it
                $this->posY++;
                continue;
            } elseif ($k !== ($nLines - 1)) {
                // re-add the newline character for each line but the last
                $line .= "\n";
            }

            ncurses_wcolor_set($this->pad, $color);
            ncurses_waddstr($this->pad, $line);
            $this->posY++;
        }

        $this->posY = $posY;

        // unbold
        if ($bold) {
            ncurses_wattroff($this->pad, NCURSES_A_BOLD);
        }

        return strlen($text);
    }

    /**
     * Indicates if a text is being pronted outside of the viewport
     * @param int $textHeight height of the text being printed (number of lines)
     * @return boolean
     */
    protected function isBeingPrintedOutside($textHeight)
    {
        $textHeight = max(0, $textHeight - 1);
        $posMin     = $this->posY;
        $posMax     = $this->posY + $textHeight;

        if ($posMin - $this->decY >= $this->padHeight || $posMax - $this->decY < 0) {
            return true;
        }

        return false;
    }

    /**
     * Expands a var and all of its children
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $var var to expand
     */
    protected function expandAll(NcursesVarDumpTypeAbstract $var)
    {
        $var->expand();
        $children = $var->getChildren();

        foreach ($children as $child) {
            $this->expandAll($child);
        }
    }

    /**
     * Move the cursor to the specified Y position
     * @param int $posY
     */
    protected function gotoPositionY($posY)
    {
        if ($this->highlightedPositionY > $posY) {
            // go up
            while ($this->highlightedPositionY > $posY) {
                $this->onKeyPress(NCURSES_KEY_UP);
            }
        } elseif ($this->highlightedPositionY < $posY) {
            // go down
            while ($this->highlightedPositionY < $posY) {
                $this->onKeyPress(NCURSES_KEY_DOWN);
            }
        }
    }

    /**
     * Cleans a string (remove non printable characters and new lines)
     * @param string $str
     * @return string
     */
    protected function cleanString($str)
    {
        $utfModifier    = preg_match("#.#u", $str) ? "u" : "";
        $cleaned        = preg_replace("#[^[:graph:][:alnum:] ]#$utfModifier", '', $str);
        $cleaned        = str_replace(array("\n", "\r"), "", $cleaned);
        return $cleaned;
    }
}
