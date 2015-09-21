<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump;

/**
 * Dumps a var to Ncurses
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class VarDumpNcurses
{
    const COLOR_DEFAULT             = 0;
    const COLOR_RED                 = 1;
    const COLOR_GREEN               = 2;
    const COLOR_YELLOW              = 3;
    const COLOR_BLUE                = 4;
    const COLOR_MAGENTA             = 5;
    const COLOR_CYAN                = 6;
    const COLOR_WHITE               = 7;
    const COLOR_HIGHLIGHTED_DEFAULT = 10;
    const COLOR_HIGHLIGHTED_RED     = 11;
    const COLOR_HIGHLIGHTED_GREEN   = 12;
    const COLOR_HIGHLIGHTED_YELLOW  = 13;
    const COLOR_HIGHLIGHTED_BLUE    = 14;
    const COLOR_HIGHLIGHTED_MAGENTA = 15;
    const COLOR_HIGHLIGHTED_CYAN    = 16;
    const COLOR_HIGHLIGHTED_WHITE   = 17;

    const SCREEN_HELP               = "help";
    const SCREEN_VAR_DUMP           = "vardump";
    const SCREEN_STACK_TRACE        = "stacktrace";

    /**
     * Screen labels
     * @var array
     */
    private static $screenLabels = array(
        self::SCREEN_HELP           => "Help",
        self::SCREEN_VAR_DUMP       => "Var dump",
        self::SCREEN_STACK_TRACE    => "Stack trace",
    );

    /**
     * Temp var used to hold references to object instances
     * @var array
     */
    private $objectIdList = array();

    /**
     * Var name
     * @var string
     */
    private $varName;

    /**
     * Identifier of the screen to display
     * @var string
     */
    private $screen = self::SCREEN_VAR_DUMP;

    /**
     * Ncurses window for the title bar
     * @var resource
     */
    private $windowTitle;

    /**
     * Width of the title window
     * @var int
     */
    private $windowTitleWidth;

    /**
     * Height of the title window
     * @var int
     */
    private $windowTitleHeight;

    /**
     * Ncurses window for the footer bar
     * @var resource
     */
    private $windowFooter;

    /**
     * Width of the footer window
     * @var int
     */
    private $windowFooterWidth;

    /**
     * Height of the footer window
     * @var int
     */
    private $windowFooterHeight;

    /**
     * Var dump ncurses renderer
     * @var \Bugzorcist\VarDump\Ncurses\NcursesVarDump
     */
    private $rendererVarDump;

    /**
     * Stack trace ncurses renderer
     * @var \Bugzorcist\VarDump\Ncurses\NcursesStackTrace
     */
    private $rendererStackTrace;

    /**
     * Help ncurses renderer
     * @var \Bugzorcist\VarDump\Ncurses\NcursesHelp
     */
    private $rendererHelp;

    /**
     * Current ncurses renderer
     * @var \Bugzorcist\VarDump\Ncurses\NcursesInterface
     */
    private $currentRenderer;

    /**
     * Ncurses window which receive user inputs
     * @var resource
     */
    private $windowDummy;

    /**
     * Dumps a var
     * @param mixed $var var to dump
     * @param string $name [optional] var name
     * @param boolean $showStackTrace [optional] indicates whether to show the stack trace. Defaults to true.
     */
    public static function dump($var, $name = "unknown var name", $showStackTrace = true)
    {
        $dump = new self($var, $name, $showStackTrace);
        $dump->render();
    }

    /**
     * Constructor
     * @param mixed $var var to dump
     * @param string $name [optional] var name
     * @param boolean $showStackTrace [optional] indicates whether to show the stack trace. Defaults to true.
     * @throws \RuntimeException
     */
    public function __construct($var, $name = "unknown var name", $showStackTrace = true)
    {
        if (!extension_loaded("ncurses")) {
            throw new \RuntimeException("The 'ncurses' PHP extension is required");
        }

        $this->varName          = (string) $name;
        $this->rendererVarDump  = new Ncurses\NcursesVarDump($var, 0, 2);
        $this->rendererHelp     = new Ncurses\NcursesHelp(0, 2);

        if ($showStackTrace) {
            $stackTrace                 = debug_backtrace(0);
            $this->rendererStackTrace   = new Ncurses\NcursesStackTrace($stackTrace, 0, 2);
        }
    }

    /**
     * Renders the dump
     */
    public function render() {
        // initialize ncurses window
        ncurses_init();
        ncurses_curs_set(0);
        ncurses_noecho();
        ncurses_cbreak();

        ncurses_start_color();
        ncurses_use_default_colors();
        ncurses_init_pair(self::COLOR_RED, NCURSES_COLOR_RED, -1);
        ncurses_init_pair(self::COLOR_GREEN, NCURSES_COLOR_GREEN, -1);
        ncurses_init_pair(self::COLOR_YELLOW, NCURSES_COLOR_YELLOW, -1);
        ncurses_init_pair(self::COLOR_BLUE, NCURSES_COLOR_BLUE, -1);
        ncurses_init_pair(self::COLOR_MAGENTA, NCURSES_COLOR_MAGENTA, -1);
        ncurses_init_pair(self::COLOR_CYAN, NCURSES_COLOR_CYAN, -1);
        ncurses_init_pair(self::COLOR_WHITE, NCURSES_COLOR_WHITE, -1);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_DEFAULT, 0, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_RED, NCURSES_COLOR_RED, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_GREEN, NCURSES_COLOR_GREEN, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_YELLOW, NCURSES_COLOR_YELLOW, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_BLUE, NCURSES_COLOR_BLUE, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_MAGENTA, NCURSES_COLOR_MAGENTA, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_CYAN, NCURSES_COLOR_CYAN, NCURSES_COLOR_WHITE);
        ncurses_init_pair(self::COLOR_HIGHLIGHTED_WHITE, NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);

        // create a dummy pad which will receive user inputs
        $this->windowDummy = ncurses_newpad(1, 1);
        ncurses_keypad($this->windowDummy, true);

        // run interface
        $continue               = true;
        $this->currentRenderer  = $this->rendererVarDump;

        $this->createTitleWindow();
        $this->createFooterWindow();
        $this->refreshTitle();
        $this->refreshFooter();

        while ($continue) {
            $this->currentRenderer->refresh();
            $key        = ncurses_wgetch($this->windowDummy);
            $continue   = $this->onKeypress($key);
        }

        ncurses_end();
    }

    /**
     * Key press event handler
     * @param int $keyCode code of the pressed key
     * @return boolean false if the ncurses session must be stopped
     */
    protected function onKeypress($keyCode)
    {
        switch ($keyCode) {
            // escape key
            // "q" key
            // "Q" key
            case 27:
            case 113:
            case 81:
                // close interface
                return false;
                break;

            // resize
            case NCURSES_KEY_RESIZE:
                $this->createTitleWindow();
                $this->createFooterWindow();
                $this->rendererVarDump->clearPad();
                $this->rendererHelp->clearPad();

                if ($this->rendererStackTrace) {
                    $this->rendererStackTrace->clearPad();
                }

                $this->refreshTitle();
                $this->refreshFooter();
                break;

            // F1
            case NCURSES_KEY_F1:
                $this->screen           = self::SCREEN_HELP;
                $this->currentRenderer  = $this->rendererHelp;
                $this->refreshTitle();
                break;

            // F2
            case NCURSES_KEY_F2:
                $this->screen           = self::SCREEN_VAR_DUMP;
                $this->currentRenderer  = $this->rendererVarDump;
                $this->refreshTitle();
                break;

            // F3
            case NCURSES_KEY_F3:
                if (null === $this->rendererStackTrace) {
                    break;
                }

                $this->screen           = self::SCREEN_STACK_TRACE;
                $this->currentRenderer  = $this->rendererStackTrace;
                $this->refreshTitle();
                break;

            default:
                $this->currentRenderer->onKeyPress($keyCode);
        }

        return true;
    }

    /**
     * Creates the title window.
     * If the title window already exists, it is deleted and recreated.
     */
    protected function createTitleWindow()
    {
        if (null !== $this->windowTitle) {
            ncurses_delwin($this->windowTitle);
        }

        $this->windowTitle = ncurses_newwin(2, 0, 0, 0);
        ncurses_getmaxyx($this->windowTitle, $this->windowTitleHeight, $this->windowTitleWidth);
        ncurses_keypad($this->windowTitle, true);
    }

    /**
     * Creates the footer window.
     * If the footer window already exists, it is deleted and recreated.
     */
    protected function createFooterWindow()
    {
        if (null !== $this->windowFooter) {
            ncurses_delwin($this->windowFooter);
        }

        $globalWidth    = null;
        $globalHeight   = null;
        ncurses_getmaxyx(STDSCR, $globalHeight, $globalWidth);

        $this->windowFooter = ncurses_newwin(1, 0, $globalHeight - 1, 0);
        ncurses_getmaxyx($this->windowFooter, $this->windowFooterHeight, $this->windowFooterWidth);
        ncurses_keypad($this->windowFooter, true);
    }

    /**
     * Refresh the title window
     */
    protected function refreshTitle()
    {
        ncurses_werase($this->windowTitle);
        ncurses_wattron($this->windowTitle, NCURSES_A_REVERSE);

        // var name
        ncurses_wcolor_set($this->windowTitle, NCURSES_COLOR_YELLOW);
        ncurses_waddstr($this->windowTitle, str_pad(" " . $this->varName, $this->windowTitleWidth, " ", STR_PAD_RIGHT));

        // screen
        $screenLabel = self::$screenLabels[$this->screen];
        ncurses_wcolor_set($this->windowTitle, NCURSES_COLOR_GREEN);
        ncurses_waddstr($this->windowTitle, str_pad(" * $screenLabel", $this->windowTitleWidth, " ", STR_PAD_RIGHT));

        ncurses_wattroff($this->windowTitle, NCURSES_A_REVERSE);
        ncurses_wrefresh($this->windowTitle);
    }

    /**
     * Refresh the footer window
     */
    protected function refreshFooter()
    {
        $length = 1;
        $labels = array(
            "F1"    => "Help",
            "F2"    => "Var dump",
            "F3"    => "Stack trace",
            "Q"     => "Quit"
        );

        if (null === $this->rendererStackTrace) {
            unset($labels["F3"]);
        }

        ncurses_werase($this->windowFooter);

        // write an empty space
        ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->windowFooter, NCURSES_COLOR_CYAN);
        ncurses_waddstr($this->windowFooter, " ");
        ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

        // write key/labels
        foreach ($labels as $key => $label) {
            ncurses_wcolor_set($this->windowFooter, 0);
            ncurses_waddstr($this->windowFooter, $key);
            ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
            ncurses_wcolor_set($this->windowFooter, NCURSES_COLOR_CYAN);
            ncurses_waddstr($this->windowFooter, "$label ");
            ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

            $length += strlen($key);
            $length += strlen($label);
            $length += 1;
        }

        // fill the line
        $fillStr = str_repeat(" ", $this->windowFooterWidth - $length);
        ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->windowFooter, NCURSES_COLOR_CYAN);
        ncurses_waddstr($this->windowFooter, $fillStr);
        ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

        ncurses_wrefresh($this->windowFooter);
    }
}
