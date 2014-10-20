<?php

namespace Bugzorcist\Exception\Renderer;

class ExceptionRendererNcurses extends ExceptionRendererAbstract
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

    const SCREEN_DESCRIPTION        = "Description";
    const SCREEN_STACK_TRACE        = "Stack Trace";
    const SCREEN_REQUEST            = "Request";
    const SCREEN_CONSTANTS          = "Constants";
    const SCREEN_USER_CONSTANTS     = "User Constants";
    const SCREEN_DATA_REQUESTS      = "Data Requests";
    const SCREEN_PROFILES           = "Profiles";
    const SCREEN_CONFIG_APPLICATION = "Application Config";
    const SCREEN_CONFIG_PHP         = "PHP Config";
    const SCREEN_INCLUDED_FILES     = "Included Files";
    const SCREEN_LOADED_EXTENSIONS  = "Loaded Extensions";
    const SCREEN_MEMORY_USAGE       = "Memory Usage";
    const SCREEN_EXECUTION_TIME     = "Execution Time";

    /**
     * List of available screens
     * @var array
     */
    private static $screenList;

    /**
     * List of screen renderers
     * @var \Bugzorcist\Exception\Render\CliNcurses\CliNcursesAbstract[]
     */
    private $screenRendererList = array();

    /**
     * Identifier of the screen to display
     * @var string
     */
    private $screen = self::SCREEN_DESCRIPTION;

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
     * Ncurses window for the window selector
     * @var resource
     */
    private $windowSelector;

    /**
     * Width of the window selector
     * @var int
     */
    private $windowSelectorWidth;

    /**
     * Height of the window selector
     * @var int
     */
    private $windowSelectorHeight;

    /**
     * Indicates if the window selector is enabled
     * @var boolean
     */
    private $windowSelectorEnabled = false;

    /**
     * Identifier of the window currently selected by the window selector
     * @var string
     */
    private $windowSelectorCurrent;

    /**
     * Ncurses window which receive user inputs
     * @var resource
     */
    private $windowDummy;

    /**
     * Constructor
     * @param \Exception $e
     * @throws \RuntimeException
     */
    public function __construct(\Exception $e)
    {
        if (!extension_loaded("ncurses")) {
            throw new \RuntimeException("The 'ncurses' PHP extension is required");
        }

        if (null === self::$screenList) {
            $reflection         = new \ReflectionClass(__CLASS__);
            $constants          = $reflection->getConstants();
            self::$screenList   = array();

            foreach ($constants as $name => $value) {
                if (0 !== strpos($name, "SCREEN_")) {
                    continue;
                }

                self::$screenList[] = $value;
            }
        }

        parent::__construct($e);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
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

        // create renderers
        $executionTime                                              = $this->_endMicrotime - $_SERVER["REQUEST_TIME_FLOAT"];
        $includedFiles                                              = get_included_files();
        $loadedExtensions                                           = get_loaded_extensions();
        $this->screenRendererList[self::SCREEN_DESCRIPTION]         = new Ncurses\NcursesDescription($this->getException(), 0, 2);
        $this->screenRendererList[self::SCREEN_STACK_TRACE]         = new Ncurses\NcursesStackTrace($this->getException()->getTrace(), 0, 2);
        $this->screenRendererList[self::SCREEN_REQUEST]             = new Ncurses\NcursesRequest(0, 2);
        $this->screenRendererList[self::SCREEN_CONSTANTS]           = new Ncurses\NcursesConstants(0, 2);
        $this->screenRendererList[self::SCREEN_USER_CONSTANTS]      = new Ncurses\NcursesUserConstants(0, 2);
        $this->screenRendererList[self::SCREEN_DATA_REQUESTS]       = new Ncurses\NcursesDataRequests(0, 2, $_SERVER["REQUEST_TIME_FLOAT"], $this->getDataProfilerManager());
        $this->screenRendererList[self::SCREEN_PROFILES]            = new Ncurses\NcursesProfiles(0, 2, $_SERVER["REQUEST_TIME_FLOAT"], $this->getProfilerManager());
        $this->screenRendererList[self::SCREEN_CONFIG_APPLICATION]  = new Ncurses\NcursesConfigApplication($this->getApplicationConfig(), 0, 2);
        $this->screenRendererList[self::SCREEN_CONFIG_PHP]          = new Ncurses\NcursesConfigPhp(0, 2);
        $this->screenRendererList[self::SCREEN_INCLUDED_FILES]      = new Ncurses\NcursesIncludedFiles($includedFiles, 0, 2);
        $this->screenRendererList[self::SCREEN_LOADED_EXTENSIONS]   = new Ncurses\NcursesLoadedExtensions($loadedExtensions, 0, 2);
        $this->screenRendererList[self::SCREEN_MEMORY_USAGE]        = new Ncurses\NcursesMemoryUsage($this->getMemoryUsage(), $this->getMemoryPeakUsage(), 0, 2);
        $this->screenRendererList[self::SCREEN_EXECUTION_TIME]      = new Ncurses\NcursesExecutionTime($executionTime, 0, 2);

        foreach ($this->screenRendererList as $idScreen => $renderer) {
            if (!$renderer->isShowable()) {
                unset($this->screenRendererList[$idScreen]);
            }
        }

        // run interface
        $continue = true;

        $this->createTitleWindow();
        $this->createFooterWindow();
        $this->createSelectorWindow();
        $this->refreshTitle();
        $this->refreshFooter();

        while ($continue) {
            $this->screenRendererList[$this->screen]->refresh();

            if ($this->windowSelectorEnabled) {
                $this->refreshWindowSelector();
            }

            $key = ncurses_wgetch($this->windowDummy);

            if ($this->windowSelectorEnabled) {
                $continue = $this->onKeyPressWindowSelector($key);
            } else {
                $continue = $this->onKeyPress($key);
            }
        }

        ncurses_end();
    }

    /**
     * Key press event handler
     * @param int $keyCode code of the pressed key
     * @return boolean false if the ncurses session must be stopped
     */
    protected function onKeyPress($keyCode)
    {
        switch ($keyCode) {
            // escape key
            // "q" key
            case 27:
            case 113:
                // close interface
                return false;
                break;

            // resize
            case NCURSES_KEY_RESIZE:
                $this->resizeAll();
                break;

            // window list
            case 119:
                $this->windowSelectorEnabled = true;
                $this->windowSelectorCurrent = $this->screen;
                break;

            // "+" key
            case 43:
                // next window
                if (empty($this->screenRendererList)) {
                    break;
                }

                $current    = array_search($this->screen, self::$screenList);
                $new        = null;

                do {
                    $new        = $current + 1;
                    $new        = array_key_exists($new, self::$screenList) ? $new : 0;
                    $current    = $new;
                } while (!array_key_exists(self::$screenList[$new], $this->screenRendererList));

                $this->screen = self::$screenList[$new];
                $this->refreshTitle();
                break;

            // "-" key
            case 45:
                // previous window
                if (empty($this->screenRendererList)) {
                    break;
                }

                $current    = array_search($this->screen, self::$screenList);
                $new        = null;

                do {
                    $new        = $current - 1;
                    $new        = array_key_exists($new, self::$screenList) ? $new : count(self::$screenList) - 1;
                    $current    = $new;
                } while (!array_key_exists(self::$screenList[$new], $this->screenRendererList));

                $this->screen = self::$screenList[$new];
                $this->refreshTitle();
                break;

            default:
                $isResizeNeeded = $this->screenRendererList[$this->screen]->onKeyPress($keyCode);

                if ($isResizeNeeded) {
                    $this->resizeAll();
                }
        }

        return true;
    }

    /**
     * Key press event handler for the window selector
     * @param int $keyCode code of the pressed key
     * @return boolean false if the ncurses session must be stopped
     */
    protected function onKeyPressWindowSelector($keyCode)
    {
        switch ($keyCode) {
            // escape key
            // "q" key
            case 27:
            case 113:
                // close interface
                return false;
                break;

            // resize
            case NCURSES_KEY_RESIZE:
                $this->resizeAll();
                break;

            // enter key
            case 13:
                $this->screen                   = $this->windowSelectorCurrent;
                $this->windowSelectorEnabled    = false;
                $this->refreshTitle();
                $this->refreshFooter();
                break;

            // up arrow
            case NCURSES_KEY_UP:
                // select previous window
                if (empty($this->screenRendererList)) {
                    break;
                }

                $current    = array_search($this->windowSelectorCurrent, self::$screenList);
                $new        = null;

                do {
                    $new        = $current - 1;
                    $new        = array_key_exists($new, self::$screenList) ? $new : count(self::$screenList) - 1;
                    $current    = $new;
                } while (!array_key_exists(self::$screenList[$new], $this->screenRendererList));

                $this->windowSelectorCurrent = self::$screenList[$new];
                break;

            // down arrow
            case NCURSES_KEY_DOWN:
                // select next window
                if (empty($this->screenRendererList)) {
                    break;
                }

                $current    = array_search($this->windowSelectorCurrent, self::$screenList);
                $new        = null;

                do {
                    $new        = $current + 1;
                    $new        = array_key_exists($new, self::$screenList) ? $new : 0;
                    $current    = $new;
                } while (!array_key_exists(self::$screenList[$new], $this->screenRendererList));

                $this->windowSelectorCurrent = self::$screenList[$new];
                break;
        }

        return true;
    }

    /**
     * Resize all ncurses windows
     */
    protected function resizeAll()
    {
        $this->createSelectorWindow();
        $this->createTitleWindow();
        $this->createFooterWindow();
        $this->refreshWindowSelector();
        $this->refreshTitle();
        $this->refreshFooter();

        foreach ($this->screenRendererList as $renderer) {
            $renderer->clearPad();
        }
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
     * Creates the selector window.
     * If the selector window already exists, it is deleted and recreated.
     */
    protected function createSelectorWindow()
    {
        if (null !== $this->windowSelector) {
            ncurses_delwin($this->windowSelector);
        }

        // calculate the width of the window
        $width = 0;

        foreach ($this->screenRendererList as $idScreen => $renderer) {
            $width = max($width, strlen($idScreen));
        }

        // creates the window
        $this->windowSelector = ncurses_newwin(0, $width + 1, 2, 0);
        ncurses_getmaxyx($this->windowSelector, $this->windowSelectorHeight, $this->windowSelectorWidth);
        ncurses_keypad($this->windowSelector, true);
    }

    /**
     * Refresh the title window
     */
    protected function refreshTitle()
    {
        ncurses_werase($this->windowTitle);
        ncurses_wattron($this->windowTitle, NCURSES_A_REVERSE);

        // exception name
        ncurses_wcolor_set($this->windowTitle, self::COLOR_YELLOW);
        ncurses_waddstr($this->windowTitle, str_pad(get_class($this->getException()), $this->windowTitleWidth, " ", STR_PAD_RIGHT));

        // screen
        $screenLabel = $this->screen;
        ncurses_wcolor_set($this->windowTitle, self::COLOR_GREEN);
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
            "W"     => "Window List",
            "+"     => "Next Window",
            "-"     => "Previous Window",
            "Q"     => "Quit",
        );

        ncurses_werase($this->windowFooter);

        // write an empty space
        ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->windowFooter, self::COLOR_CYAN);
        ncurses_waddstr($this->windowFooter, " ");
        ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

        // write key/labels
        foreach ($labels as $key => $label) {
            ncurses_wcolor_set($this->windowFooter, 0);
            ncurses_waddstr($this->windowFooter, $key);
            ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
            ncurses_wcolor_set($this->windowFooter, self::COLOR_CYAN);
            ncurses_waddstr($this->windowFooter, "$label ");
            ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

            $length += strlen($key);
            $length += strlen($label);
            $length += 1;
        }

        // fill the line
        $fillStr = str_repeat(" ", $this->windowFooterWidth - $length);
        ncurses_wattron($this->windowFooter, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->windowFooter, self::COLOR_CYAN);
        ncurses_waddstr($this->windowFooter, $fillStr);
        ncurses_wattroff($this->windowFooter, NCURSES_A_REVERSE);

        ncurses_wrefresh($this->windowFooter);
    }

    /**
     * Resfresh the window selector
     */
    protected function refreshWindowSelector()
    {
        ncurses_werase($this->windowSelector);
        ncurses_wattron($this->windowSelector, NCURSES_A_REVERSE);
        ncurses_wcolor_set($this->windowSelector, self::COLOR_CYAN);

        foreach ($this->screenRendererList as $idScreen => $renderer) {
            if ($this->windowSelectorCurrent == $idScreen) {
                ncurses_wcolor_set($this->windowSelector, self::COLOR_WHITE);
            }

            $idScreen = str_pad($idScreen, $this->windowSelectorWidth, " ", STR_PAD_RIGHT);
            ncurses_waddstr($this->windowSelector, $idScreen);
            ncurses_wcolor_set($this->windowSelector, self::COLOR_CYAN);
        }

        for ($i = count($this->screenRendererList); $i < $this->windowSelectorHeight; $i++) {
            ncurses_waddstr($this->windowSelector, str_repeat(" ", $this->windowSelectorWidth));
        }

        ncurses_wattroff($this->windowSelector, NCURSES_A_REVERSE);
        ncurses_wrefresh($this->windowSelector);
    }
}
