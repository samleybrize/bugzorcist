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

    // TODO todel
    /**
     * Var tree
     * @var array
     */
    private $varTree;

    // TODO
    private $var;

    /**
     * Temp var used to hold references to object instances
     * @var array
     */
    private $objectIdList = array();

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

    // TODO todel
    /**
     * Indicates if internal write buffer is enabled
     * @var boolean
     */
    private $internalWriteEnabled = false;

    // TODO todel
    /**
     * Internal write buffer
     * @var array
     */
    private $internalWriteBuffer = array();

    /**
     * List of colors that must be displayed as bold text
     * @var array
     */
    private $boldColorList = array(
        VarDumpNcurses::COLOR_BLUE,
        VarDumpNcurses::COLOR_MAGENTA,
    );

    /**
     * UID of last clicked clone object
     * @var string
     */
    private $cloneObjectUidSrc;

    /**
     * UID of last clicked clone object's referenced object
     * @var string
     */
    private $cloneObjectUidDst;

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
     * @var array
     */
    private $searchFoundUidList = array();

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
        $this->varTree          = $varTree->getTree(); // TODO todel
        $this->var              = NcursesVarDumpTypeAbstract::factory($this->varTree);
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

            // TODO F9
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
                        $this->searchFoundUidList       = array();
                        break;
                    } elseif (13 === $searchKeyCode) {
                        // end input if pressed key is ENTER
                        $this->editSearchPad            = false;
                        $this->searchFoundOccurences    = 0;
                        $this->searchFoundUidList       = array();

                        $this->internalWriteEnabled     = true;
                        $this->refresh();

                        $this->internalWriteEnabled     = false;
                        $this->refresh();

                        $this->searchFoundUidList       = array_unique($this->searchFoundUidList);
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

            // enter key
            case 13:
                // expand array/object/string
                if (array_key_exists($this->highlightedPositionY, $this->expandableList)) {
                    $element = $this->expandableList[$this->highlightedPositionY];

                    // TODO
                    if (0 && array_key_exists("clone", $element) && $element["clone"]) {
                        // cloned element
                        // expand all elements from referenced object to root

                        if (!$this->expandFromReferencedObjectToRoot($element["id"], $this->varTree)) {
                            return;
                        }

                        $this->refresh();

                        // find found object's new y position
                        $dstY = $this->getObjectPositionY($element["id"]);

                        // goto to y position
                        if (false !== $dstY) {
                            $this->gotoPositionY($dstY);
                        }

                        // hold UIDs for highlighting
                        $this->cloneObjectUidSrc = $element["uid"];
                        $this->cloneObjectUidDst = $this->findReferencedObject($element["id"], $this->varTree);
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
        if (null === $this->pad) {
            $this->createPad();
            ncurses_init_pair(self::COLOR_REF_OBJECT_SRC, NCURSES_COLOR_BLACK, NCURSES_COLOR_YELLOW);
            ncurses_init_pair(self::COLOR_REF_OBJECT_SRC + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_YELLOW);
            ncurses_init_pair(self::COLOR_REF_OBJECT_DST, NCURSES_COLOR_BLACK, NCURSES_COLOR_RED);
            ncurses_init_pair(self::COLOR_REF_OBJECT_DST + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_RED);
            ncurses_init_pair(self::COLOR_SEARCH_MATCH, NCURSES_COLOR_BLACK, NCURSES_COLOR_CYAN);
            ncurses_init_pair(self::COLOR_SEARCH_MATCH + 10, NCURSES_COLOR_BLACK, NCURSES_COLOR_CYAN);
        }

        ncurses_werase($this->pad);
        $this->setPositionXY(0, 0);

        $this->maxY                     = $this->var->getStringHeight() + $this->var->getChildrenHeight(); // TODO problem
        $this->expandableList           = array();
        $this->highlightRefYList        = array();

        $this->objectIdList = array();
        $locale             = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, "C");
        $this->renderVar2($this->var);
        setlocale(LC_NUMERIC, $locale);
        $this->objectIdList = array();

        ncurses_prefresh(
            $this->pad,
            0, $this->decX,
            $this->padPositionY, $this->padPositionX,
            $this->padHeight + $this->padPositionY - 1, $this->padWidth - 1
        );

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
    protected function renderVar2(NcursesVarDumpTypeAbstract $var, $level = 0)
    {
        if ($this->isBeingPrintedOutside2($var->getStringHeight() + $var->getChildrenHeight())) {
            return;
        }

        $strArray   = $var->getStringArray();
        $children   = $var->getChildren();
        $color      = null;
        $str        = null;

        // add var to expandable list
        if ($var->isExpandable()) {
            $this->expandableList[$this->posY] = $var;
        }

        // render string
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

        $this->addPosition(0, $var->getStringHeight());
        $level++;

        // render children
        foreach ($children as $child) {
            $this->printRawText(str_repeat("    ", $level));
            $this->renderVar2($child, $level);
        }
    }

    // TODO todel
    /**
     * Renders the var
     * @param array $tree var tree
     * @param int $level [optional] depth level
     * @throws \UnexpectedValueException
     */
    protected function renderVar(&$tree, $level = 0)
    {
        switch ($tree["type"]) {
            // string
            case "string":
                // make expandable
                if (!array_key_exists("expanded", $tree)) {
                    $tree["expanded"] = false;
                }

                $this->expandableList[$this->posY] = &$tree;

                // limits string length
                $limit      = strpos($tree["value"], "\n");
                $limit      = false !== $limit ? $limit : $tree["length"];
                $string     = $tree["value"];
                $symbol     = null;

                if ($tree["length"] > $limit && !$tree["expanded"] && !$this->internalWriteEnabled) {
                    $symbol     = " ▸";
                    $string     = substr($tree["value"], 0, $limit) . "...";
                    $render     = "<<4>>string<<0>>(<<1>>{$tree["length"]}<<0>>) ";
                } else {
                    $render     = "<<4>>string<<0>>(<<1>>{$tree["length"]}<<0>>) ";
                }

                // render string
                $written    = $this->printText($render, $tree["uid"]);
                $newlines   = substr_count($string, "\n");
                $this->addPosition($written, 0);
                $this->printText("<<1>>\"$string\"<<0>>");

                if ($symbol) {
                    $this->printRawText($symbol, VarDumpNcurses::COLOR_DEFAULT);
                }

                for ($i = $this->posY + 1; $i <= $this->posY + $newlines; $i++) {
                    // in case of a multiline string, lines other than the first are marked as unselectable
                    $this->highlightRefYList[$i] = $this->posY;
                }

                $this->addPosition(0, 1 + $newlines);
                break;

            // number
            case "integer":
            case "long":
            case "float":
            case "double":
                $render = "<<4>>{$tree["type"]}<<0>>(<<1>>{$tree["value"]}<<0>>)";
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);
                break;

            // boolean
            case "bool":
            case "boolean":
                $render = "<<4>>bool<<0>>(<<2>>{$tree["value"]}<<0>>)";
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);
                break;

            // null
            case "null":
            case "NULL":
                $render = "<<2>>null";
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);
                break;

            // resource
            case "resource":
                $render = "<<4>>resource<<0>>({$tree["value"]})";
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);
                break;

            // array
            case "array":
                // make expandable
                if (!array_key_exists("expanded", $tree)) {
                    $tree["expanded"] = false;
                }

                $this->expandableList[$this->posY] = &$tree;

                // render array
                $pad        = str_repeat(" ", $level * 4);
                $render     = "<<4>>array<<0>>(<<1>>{$tree["count"]}<<0>>) ";
                $render    .= $tree["expanded"] ? "▾" : "▸";
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);

                if ($tree["expanded"] || $this->internalWriteEnabled) {
                    foreach ($tree["children"] as $k => $v) {
                        $this->setPositionX(0);

                        $render = "$pad    [$k] = ";
                        $this->printText($render, $tree["uid"]);
                        $this->addPosition(strlen($render), 0);

                        $this->renderVar($tree["children"][$k], $level + 1);
                        $this->addPosition(-$pad - strlen($render), 0);
                    }
                }

                break;

            // object
            case "object":
                // if this object instance has already been processed, we copy it
                if (!array_key_exists("class", $tree) && array_key_exists($tree["id"], $this->objectIdList)) {
                    $ref                = $this->objectIdList[$tree["id"]];
                    $tree["class"]      = $ref["class"];
                    $tree["count"]      = $ref["count"];
                    $tree["clone"]      = true;
                    $tree["expanded"]   = false;
                } elseif (!array_key_exists("clone", $tree)) {
                    // mark as not cloned
                    $tree["clone"]      = false;
                }

                // make expandable
                if (!array_key_exists("expanded", $tree)) {
                    $tree["expanded"] = false;
                }

                $this->expandableList[$this->posY] = &$tree;

                // render object instance
                $this->objectIdList[$tree["id"]]    = $tree;
                $idColor                            = 6;

                if ($this->cloneObjectUidSrc === $tree["uid"]) {
                    $idColor = self::COLOR_REF_OBJECT_SRC;
                } elseif ($this->cloneObjectUidDst === $tree["uid"]) {
                    $idColor = self::COLOR_REF_OBJECT_DST;
                }

                $pad        = str_repeat(" ", $level * 4);
                $rightArrow = $tree["clone"] ? ">>" : "▸";
                $render     = "<<4>>object<<0>>(<<5>>{$tree["class"]}<<0>>)";
                $render    .= "<<$idColor>>#{$tree["id"]}<<0>> (<<1>>{$tree["count"]}<<0>>) ";
                $render    .= ($tree["expanded"] && !$tree["clone"]) ? "▾" : $rightArrow;
                $this->printText($render, $tree["uid"]);
                $this->addPosition(0, 1);

                if (($tree["expanded"] || $this->internalWriteEnabled) && !$tree["clone"]) {
                    foreach ($tree["properties"] as $k => $v) {
                        $this->setPositionX(0);

                        $class      = $v["class"] ? "<<5>>{$v["class"]}:<<0>>" : "";
                        $key        = $v["static"] ?
                            "<<2>>static:<<3>>{$v["access"]}:<<0>>{$class}{$v["name"]}" :
                            "<<3>>{$v["access"]}:<<0>>{$v["name"]}"
                        ;
                        $render     = "$pad    [$key] = ";
                        $written    = $this->printText($render, $tree["uid"]);
                        $this->addPosition($written, 0);

                        $this->renderVar($tree["properties"][$k]["value"], $level + 1);
                        $this->addPosition(-$pad - strlen($render), 0);
                    }
                }

                break;

            // unknown type
            default:
                throw new \UnexpectedValueException("Unknown var type '{$level["type"]}'");
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

    // TODO todel
    /**
     * Prints a text. The text may contain color tags like "<<4>>" where "4" is the color number as declared by the VarDumpNcurses::COLOR_* constants.
     * @param string $text text to print
     * @param string $uid [optional] element UID of the text being printed
     * @return int number of characters printed
     */
    protected function printText($text, $uid = null)
    {
        // don't want to write outside of the viewport
        if (!$this->internalWriteEnabled && $this->isBeingPrintedOutside($text)) {
            return 0;
        }

        // search for color tags
        $this->pushPositionState();

        $matches    = array();
        $pattern    = "#<<[0-9]+>>#";
        preg_match_all($pattern, $text, $matches);
        $matches    = $matches[0];

        $text       = preg_split($pattern, $text);
        $text       = array_reverse(array_reverse($text));

        // if the text does not begin with a color tag, we add one with default color
        if (count($text) > count($matches)) {
            array_unshift($matches, "<<0>>");
        }

        // text search
        $searchStart        = 0;
        $textInline         = implode("", $text);
        $textInlineLength   = strlen($textInline);

        while (
            null !== $this->searchText &&
            "" !== $this->searchText &&
            $searchStart <= $textInlineLength &&
            false !== ($searchPos = stripos($textInline, $this->searchText, $searchStart))
        ) {
            // found text
            $curPos             = 0;
            $searchLength       = strlen($this->searchText);
            $replaceTextList    = array();
            $replaceColorList   = array();

            if ($this->internalWriteEnabled) {
                $this->searchFoundOccurences++;

                if ($uid) {
                    $this->searchFoundUidList[] = $uid;
                }
            }

            // identify pieces that match search text
            // for the text search "rt", replacement is :
            // aze[rt]y        => aze / rt / y
            // aze[r] / [t]y   => aze / rt / y
            foreach ($text as $k => $t) {
                $tLength = strlen($t);

                if ($searchPos >= $curPos && $searchPos < $curPos + $tLength) {
                    $found          = substr($t, $searchPos - $curPos, $searchLength);
                    $foundLength    = strlen($found);
                    $pre            = substr($t, 0, $searchPos - $curPos);
                    $post           = substr($t, $searchPos - $curPos + $foundLength);

                    if (!array_key_exists($k, $replaceTextList)) {
                        $replaceTextList[$k]    = array();
                        $replaceColorList[$k]   = array();
                    }

                    // add text before match to replacement
                    if ($pre) {
                        $replaceTextList[$k][]  = $pre;
                        $replaceColorList[$k][] = $matches[$k];
                    }

                    // add matched text
                    $replaceTextList[$k][]  = $found;
                    $replaceColorList[$k][] = "<<" . self::COLOR_SEARCH_MATCH . ">>";

                    // add text after match to replacement
                    if ($post) {
                        $replaceTextList[$k][]  = $post;
                        $replaceColorList[$k][] = $matches[$k];
                    }

                    // for further processing
                    $searchPos     += $foundLength;
                    $searchLength  -= $foundLength;
                }

                if ($searchLength <= 0) {
                    // there is no more text to search
                    break;
                }

                $curPos += $tLength;
            }

            // do replacement
            krsort($replaceTextList);

            foreach ($replaceTextList as $k => $replace) {
                $text       = array_merge(array_slice($text, 0, $k), $replace, array_slice($text, $k + 1));
                $matches    = array_merge(array_slice($matches, 0, $k), $replaceColorList[$k], array_slice($matches, $k + 1));
            }

            // to find next occurence in same text
            $searchStart += $searchPos;
        }

        // print colored text
        $written    = 0;

        foreach ($text as $k => $t) {
            $color      = array_key_exists($k, $matches) ? (int) substr($matches[$k], 2) : 0;
            $length     = strlen($t);
            $written   += $length;

            $this->printRawText($t, $color);
        }

        $this->popPositionState();
        return $written;
    }

    /**
     * Prints a text to the pad
     * @param string $text text to print
     * @param int $color [optional] text color. One of the VarDumpNcurses::COLOR_* constants. Defaults to VarDumpNcurses::COLOR_DEFAULT.
     * @return int number of characters printed
     */
    protected function printRawText($text, $color = VarDumpNcurses::COLOR_DEFAULT)
    {
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

        // print the text
        ncurses_wcolor_set($this->pad, $color);
        ncurses_waddstr($this->pad, $text);

        // unbold
        if ($bold) {
            ncurses_wattroff($this->pad, NCURSES_A_BOLD);
        }

        return strlen($text);
    }

    // TODO todel
    /**
     * Indicates if a text is being pronted outside of the viewport
     * @param string $text text being printed
     * @return boolean
     */
    protected function isBeingPrintedOutside($text)
    {
        $posMin = $this->posY;
        $posMax = $this->posY + substr_count($text, "\n");

        if ($posMin - $this->decY >= $this->padHeight || $posMax - $this->decY < 0) {
            return true;
        }

        return false;
    }

    // TODO
    protected function isBeingPrintedOutside2($textHeight)
    {
        $posMin = $this->posY;
        $posMax = $this->posY + $textHeight;

        if ($posMin - $this->decY >= $this->padHeight || $posMax - $this->decY < 0) {
            return true;
        }

        return false;
    }

    // TODO
    protected function isYPosBelowViewport()
    {
        return $this->posY - $this->decY >= $this->padHeight;
    }

    // TODO
    /**
     * Find non-clone object by its identifier
     * @param string $idObject
     * @param array $tree tree to search in
     * @return string found object UID
     */
    protected function findReferencedObject($idObject, array $tree)
    {
        if ("object" == $tree["type"] && !$tree["clone"]) {
            // check if it is the searched object
            if ($idObject === $tree["id"]) {
                // object found
                return $tree["uid"];
            } else {
                // explore child elements
                foreach ($tree["properties"] as &$property) {
                    if ($uid = $this->findReferencedObject($idObject, $property["value"])) {
                        return $uid;
                    }
                }
            }
        } elseif ("array" == $tree["type"]) {
            // explore child elements
            foreach ($tree["children"] as &$child) {
                if ($uid = $this->findReferencedObject($idObject, $child)) {
                    return $uid;
                }
            }
        }

        return false;
    }

    // TODO
    /**
     * Expands an object and all of its ancestors (clones are ignored)
     * @param string $idObject object identifier
     * @param array $tree tree to search in
     * @return boolean
     */
    protected function expandFromReferencedObjectToRoot($idObject, array &$tree)
    {
        $found = false;

        if ("object" == $tree["type"] && !$tree["clone"]) {
            // check if it is the searched object
            if ($idObject === $tree["id"]) {
                // object found
                $found = true;
            } else {
                // explore child elements
                foreach ($tree["properties"] as &$property) {
                    if ($this->expandFromReferencedObjectToRoot($idObject, $property["value"])) {
                        $found = true;
                        break;
                    }
                }
            }
        } elseif ("array" == $tree["type"]) {
            // explore child elements
            foreach ($tree["children"] as &$child) {
                if ($this->expandFromReferencedObjectToRoot($idObject, $child)) {
                    $found = true;
                    break;
                }
            }
        }

        // expand if object found in one of its child elements
        if ($found) {
            $tree["expanded"] = true;
        }

        return $found;
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

    // TODO
    /**
     * Returns the Y position of an object (original instance, clones are ignored)
     * @param string $idObject object identifier
     */
    protected function getObjectPositionY($idObject)
    {
        foreach ($this->expandableList as $y => $expandable) {
            if ("object" !== $expandable["type"] || $idObject !== $expandable["id"] || $expandable["clone"]) {
                continue;
            }

            return $y;
        }

        return false;
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
