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

use Bugzorcist\VarDump\VarDumpCliNcurses;
use Bugzorcist\VarDump\VarTree;

class NcursesVarDump implements NcursesInterface
{
    /**
     * Var tree
     * @var array
     */
    private $varTree;

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
     * Saved position list
     * @var array
     */
    private $positionStateList = array();

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
     * Indicates if internal write buffer is enabled
     * @var boolean
     */
    private $internalWriteEnabled = false;

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
        VarDumpCliNcurses::COLOR_BLUE,
        VarDumpCliNcurses::COLOR_MAGENTA,
    );

    /**
     * Constructor
     * @param mixed $var var to dump
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($var, $padPositionX, $padPositionY)
    {
        $varTree                = new VarTree($var);
        $this->varTree          = $varTree->getTree();
        $this->padPositionX     = (int) $padPositionX;
        $this->padPositionY     = (int) $padPositionY;
    }

    /**
     * Calculates the real width of the pad
     */
    protected function calculatePadRealSize()
    {
        // initialize internal write buffer
        $this->internalWriteBuffer  = array();
        $this->internalWriteEnabled = true;

        if (null === $this->pad) {
            // creates a dummy pad if none has been created
            $this->pad = ncurses_newpad(1, 1);
        }

        $this->refresh();

        // disable internal write buffer
        ncurses_delwin($this->pad);
        $this->pad                  = null;
        $this->internalWriteEnabled = false;

        // retrieve the size of the largest line as the width
        $this->padRealWidth         = 1;

        foreach ($this->internalWriteBuffer as $line) {
            $this->padRealWidth = max($this->padRealWidth, strlen($line));
        }
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

        if (false === $this->pad) {
            throw new \RuntimeException("Failed to create a ncurses pad (width: $this->padRealWidth, height: $this->padRealHeight)");
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
            $this->pad = null;
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
                foreach ($this->expandableList as $k => $tree) {
                    $this->expandableList[$k]["expanded"] = false;
                }
                break;

            // F6
            case NCURSES_KEY_F6:
                // expand all visible elements
                foreach ($this->expandableList as $k => $tree) {
                    $this->expandableList[$k]["expanded"] = true;
                }
                break;

            // F8
            case NCURSES_KEY_F8:
                // toggle cursor highlight
                $this->cursorHighlight = !$this->cursorHighlight;
                break;

            // enter key
            case 13:
                // expand array/object/string
                if (array_key_exists($this->highlightedPositionY, $this->expandableList) &&
                        array_key_exists("expanded", $this->expandableList[$this->highlightedPositionY])) {
                    $this->expandableList[$this->highlightedPositionY]["expanded"] = !$this->expandableList[$this->highlightedPositionY]["expanded"];

                    // if the selected line is not the first line of the element, go up until the first line
                    if ($this->cursorPositionY != $this->highlightedPositionY) {
                        $up = $this->cursorPositionY - $this->highlightedPositionY;

                        for ($i = 0; $i < $up; $i++) {
                            $this->onKeyPress(259);
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
                        if ($newCursorPositionY - $this->decY >= $this->padHeight) {
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
                        if ($newCursorPositionY - $this->decY >= $this->padHeight) {
                            $this->decY = min(
                                $newCursorPositionY,
                                $this->maxY - $this->padHeight
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
                $this->decX = min($this->padRealWidth - $this->padWidth, $this->decX + $this->padWidth);
                break;

            // ctrl + left arrow
            case 540:
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
        }

        ncurses_werase($this->pad);
        $this->setPositionXY(0, 0);

        $this->maxY                 = 0;
        $this->expandableList       = array();
        $this->highlightRefYList    = array();

        $this->objectIdList = array();
        $locale             = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, "C");
        $this->renderVar($this->varTree);
        setlocale(LC_NUMERIC, $locale);
        $this->objectIdList = array();

        ncurses_prefresh(
            $this->pad,
            0, $this->decX,
            $this->padPositionY, $this->padPositionX,
            $this->padHeight + $this->padPositionY - 1, $this->padWidth - 1
        );
    }

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
                $written    = $this->printText($render);
                $newlines   = substr_count($string, "\n");
                $this->addPosition($written, 0);
                $this->printRawText("\"$string\"", VarDumpCliNcurses::COLOR_RED);

                if ($symbol) {
                    $this->printRawText($symbol, VarDumpCliNcurses::COLOR_DEFAULT);
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
                $this->printText($render);
                $this->addPosition(0, 1);
                break;

            // boolean
            case "bool":
            case "boolean":
                $render = "<<4>>bool<<0>>(<<2>>{$tree["value"]}<<0>>)";
                $this->printText($render);
                $this->addPosition(0, 1);
                break;

            case "null":
            case "NULL":
                $render = "<<2>>null";
                $this->printText($render);
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
                $this->printText($render);
                $this->addPosition(0, 1);

                // TODO when an object instance appears recursively and $this->internalWriteEnabled = true, infinite loop (ex: $a->b = $a)
                if ($tree["expanded"] || $this->internalWriteEnabled) {
                    foreach ($tree["children"] as $k => $v) {
                        $this->setPositionX(0);

                        $render = "$pad    [$k] = ";
                        $this->printText($render);
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
                    $ref = $this->objectIdList[$tree["id"]];

                    foreach ($ref as $k => $v) {
                        $tree[$k] = $v;
                    }

                    $tree["expanded"] = false;
                }

                // make expandable
                if (!array_key_exists("expanded", $tree)) {
                    $tree["expanded"] = false;
                }

                $this->expandableList[$this->posY] = &$tree;

                // render object instance
                $this->objectIdList[$tree["id"]] = $tree;

                $pad        = str_repeat(" ", $level * 4);
                $render     = "<<4>>object<<0>>(<<5>>{$tree["class"]}<<0>>)";
                $render    .= "<<6>>#{$tree["id"]} <<0>>(<<1>>{$tree["count"]}<<0>>) ";
                $render    .= $tree["expanded"] ? "▾" : "▸";
                $this->printText($render);
                $this->addPosition(0, 1);

                if ($tree["expanded"] || $this->internalWriteEnabled) {
                    foreach ($tree["properties"] as $k => $v) {
                        $this->setPositionX(0);

                        $key        = $v["static"] ?
                            "<<2>>static:<<3>>{$v["access"]}:<<0>>{$v["name"]}" :
                            "<<3>>{$v["access"]}:<<0>>{$v["name"]}"
                        ;
                        $render     = "$pad    [$key] = ";
                        $written    = $this->printText($render);
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

    /**
     * Saves current position and add it to the end of the saved list
     */
    protected function pushPositionState()
    {
        $this->positionStateList[] = array(
            "x" => $this->posX,
            "y" => $this->posY
        );
    }

    /**
     * Restores last saved position and remove it from the saved list
     */
    protected function popPositionState()
    {
        $state = array_pop($this->positionStateList);

        if (null === $state) {
            return;
        }

        $this->setPositionXY($state["x"], $state["y"]);
    }

    /**
     * Prints a text. The text may contain color tags like "<<4>>" where "4" is the color number as declared by the VarDumpCliNcurses::COLOR_* constants.
     * @param string $text text to print
     * @return int number of characters printed
     */
    protected function printText($text)
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
     * @param int $color [optional] text color. One of the VarDumpCliNcurses::COLOR_* constants. Defaults to VarDumpCliNcurses::COLOR_DEFAULT.
     * @return int number of characters printed
     */
    protected function printRawText($text, $color = VarDumpCliNcurses::COLOR_DEFAULT)
    {
        if ($this->internalWriteEnabled) {
            return $this->printRawTextInternal($text);
        }

        // don't want to write outside of the viewport
        if ($this->isBeingPrintedOutside($text)) {
            return 0;
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

        // print the text
        ncurses_wcolor_set($this->pad, $color);
        ncurses_waddstr($this->pad, $text);

        // unbold
        if ($bold) {
            ncurses_wattroff($this->pad, NCURSES_A_BOLD);
        }

        return strlen($text);
    }

    /**
     * Prints a text to the internal buffer
     * @param string $text text to print
     * @return int number of characters printed
     */
    protected function printRawTextInternal($text)
    {
        // creates required lines into the internal buffer
        $text       = explode("\n", $text);
        $lineCount  = count($text);

        for ($line = 0; $line < $this->posY + $lineCount; $line++) {
            if (!array_key_exists($line, $this->internalWriteBuffer)) {
                $this->internalWriteBuffer[$line] = "";
            }
        }

        // write to the internal buffer
        $i = 0;

        for ($line = $this->posY; $line < $this->posY + $lineCount; $line++) {
            $this->internalWriteBuffer[$line] .= $text[$i];
            $i++;
        }
    }

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
}