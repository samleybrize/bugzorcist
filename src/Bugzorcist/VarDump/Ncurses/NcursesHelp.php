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

/**
 * Ncurses help viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesHelp implements NcursesInterface
{
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
        VarDumpNcurses::COLOR_MAGENTA,
    );

    /**
     * Constructor
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     */
    public function __construct($padPositionX, $padPositionY)
    {
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
        $this->padRealHeight        = count($this->internalWriteBuffer);

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

        $this->padWidth         = $globalWidth;
        $this->padHeight        = $globalHeight - 3;
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
            // up arrow
            case NCURSES_KEY_UP:
                $this->decY = max(0, $this->decY - 1);
                break;

            // down arrow
            case NCURSES_KEY_DOWN:
                $this->decY++;

                if ($this->decY > $this->padRealHeight - $this->padHeight) {
                    $this->decY = max(0, $this->padRealHeight - $this->padHeight);
                }
                break;

            // page up
            case NCURSES_KEY_PPAGE:
                $this->decY = max(0, $this->decY - $this->padHeight);
                break;

            // page down
            case NCURSES_KEY_NPAGE:
                $this->decY += $this->padHeight;

                if ($this->decY > $this->padRealHeight - $this->padHeight) {
                    $this->decY = max(0, $this->padRealHeight - $this->padHeight);
                }
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
        ncurses_wmove($this->pad, 0, 0);

        $this->renderHelp();

        ncurses_prefresh(
            $this->pad,
            $this->decY, $this->decX,
            $this->padPositionY, $this->padPositionX,
            $this->padHeight + $this->padPositionY - 1, $this->padWidth - 1
        );
    }

    /**
     * Renders the help
     */
    protected function renderHelp()
    {
        $help = array(
            "Var dump" => array(
                "F5"                => "Collapse all",
                "F6"                => "Expand all visible elements",
                "F7"                => "Expand all",
                "F8"                => "Toggle highlighting",
                "Enter"             => "Expand/collapse highlighted element",
                "Up/down arrow"     => "Move the cursor vertically",
                "Left/right arrow"  => "Scroll the window horizontally",
                "Page up/down"      => "Move the cursor to the next/previous page",
                "Home/end"          => "Scroll the window horizontally to the beginning/end",
                "Ctrl+right/left"   => "Scroll the window horizontally",
            ),
            "Stack trace" => array(
                "Up/down arrow"     => "Scroll the window vertically",
                "Left/right arrow"  => "Scroll the window horizontally",
                "Page up/down"      => "Scroll the window vertically to the next/previous page",
                "Home/end"          => "Scroll the window horizontally to the beginning/end",
                "Ctrl+right/left"   => "Scroll the window horizontally",
            )
        );

        foreach ($help as $catName => $definitionList) {
            $this->printRawText("\n$catName\n", VarDumpNcurses::COLOR_MAGENTA);

            foreach ($definitionList as $key => $definition) {
                $this->printRawText("    $key\n", VarDumpNcurses::COLOR_CYAN);
                $this->printRawText("        $definition\n", VarDumpNcurses::COLOR_DEFAULT);
            }
        }
    }

    /**
     * Prints a text. The text may contain color tags like "<<4>>" where "4" is the color number as declared by the VarDumpNcurses::COLOR_* constants.
     * @param string $text text to print
     * @return int number of characters printed
     */
    protected function printText($text)
    {
        // search for color tags
        $matches    = array();
        $pattern    = "#<<[0-9]+>>#";
        preg_match_all($pattern, $text, $matches);
        $matches    = $matches[0];

        $text       = preg_split($pattern, $text);
        $text       = array_filter($text, function($var) {
            // string "0" is rejected by default
            // so we test the string length instead
            if (is_string($var)) {
                return strlen($var) > 0;
            } else {
                return !empty($var);
            }
        });
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
        if ($this->internalWriteEnabled) {
            return $this->printRawTextInternal($text);
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
        if (empty($this->internalWriteBuffer)) {
            $this->internalWriteBuffer[] = "";
        }

        // creates required lines into the internal buffer
        $text           = explode("\n", $text);
        $lineCount      = count($text);
        $currentLine    = count($this->internalWriteBuffer) - 1;

        for ($line = 1; $line < $lineCount; $line++) {
            $this->internalWriteBuffer[] = "";
        }

        // write to the internal buffer
        $i = 0;

        for ($line = $currentLine; $line < $currentLine + $lineCount; $line++) {
            $this->internalWriteBuffer[$line] .= $text[$i];
            $i++;
        }
    }
}
