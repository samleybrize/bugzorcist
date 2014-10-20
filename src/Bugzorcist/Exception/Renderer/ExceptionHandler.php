<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer;

use Bugzorcist\Profiler\DataProfiler\DataProfilerManager;
use Bugzorcist\Profiler\Profiler\ProfilerManager;

/**
 * Exception handler to render uncaught exceptions
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ExceptionHandler
{
    /**
     * Application config
     * @var mixed
     */
    private $applicationConfig;

    /**
     * Data profiler manager
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfilerManager
     */
    private $dataProfilerManager;

    /**
     * Profiler manager
     * @var \Bugzorcist\Profiler\Profiler\ProfilerManager
     */
    private $profilerManager;

    /**
     * Previously defined exception handler
     * @var callback
     */
    private $previousExceptionHandler;

    /**
     * Indicates if an exception has been processed
     * @var boolean
     */
    private $handledException = false;

    /**
     * Sets the application config
     * @param mixed $config
     */
    public function setApplicationConfig($config)
    {
        $this->applicationConfig = $config;
    }

    /**
     * Sets the profiler manager
     * @param \Bugzorcist\Profiler\Profiler\ProfilerManager $profilerManager
     */
    public function setProfilerManager(ProfilerManager $profilerManager)
    {
        $this->profilerManager = $profilerManager;
    }

    /**
     * Sets the data profiler manager
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfilerManager $dataProfilerManager
     */
    public function setDataProfilerManager(DataProfilerManager $dataProfilerManager)
    {
        $this->dataProfilerManager = $dataProfilerManager;
    }

    /**
     * Registers this object as an exception handler. It will then handle all uncaught exceptions, and call the previously defined exception handler.
     */
    public function registerHandler()
    {
        $this->previousExceptionHandler = set_exception_handler(array($this, "handleException"));
    }

    /**
     * Throws an exception at the end of the PHP process
     */
    public function registerShutdown()
    {
        register_shutdown_function(array($this, "onShutdown"));
    }

    /**
     * Handles an exception and render it
     * @param \Exception $e
     * @param boolean $callPreviousHandler [optional] if set to true, call the previously defined exception handler, if any. Defaults to true.
     */
    public function handleException(\Exception $e, $callPreviousHandler = true)
    {
        // create a renderer depending on the context
        if ("cli" == PHP_SAPI) {
            if (posix_isatty(STDOUT)) {
                // attempt to create the ncurses based renderer
                try {
                    $renderer = new ExceptionRendererNcurses($e);
                } catch (\RuntimeException $e) {
                    // failed to create the ncurses based renderer, fallback to the cli renderer
                    $renderer = new ExceptionRendererCli($e);
                }
            } else {
                // cli renderer
                $renderer = new ExceptionRendererCli($e);
            }
        } elseif (array_key_exists("HTTP_X_REQUESTED_WITH", $_SERVER) && "XMLHttpRequest" == $_SERVER["HTTP_X_REQUESTED_WITH"]) {
            // firephp renderer
            $renderer = new ExceptionRendererFirephp($e);
        } else {
            // html renderer
            $renderer = new ExceptionRendererHtml($e);
        }

        // render
        if ($this->applicationConfig) {
            $renderer->setApplicationConfig($this->applicationConfig);
        }

        if ($this->profilerManager) {
            $renderer->setProfilerManager($this->profilerManager);
        }

        if ($this->dataProfilerManager) {
            $renderer->setDataProfilerManager($this->dataProfilerManager);
        }

        $renderer->render();
        $this->handledException = true;

        // on appelle le handler précédent
        if ($this->previousExceptionHandler && $callPreviousHandler) {
            call_user_func($this->previousExceptionHandler, $e);
        }
    }

    /**
     * Callback for "registerShutdown()".
     * Renders an exception at the very end of the PHP execution.
     */
    public function onShutdown()
    {
        // if an exception has been processed, don't do anything
        if ($this->handledException) {
            return;
        }

        // clean output buffers
        $level = ob_get_level();

        for ($i = $level; $i > 0; $i--) {
            ob_end_clean();
        }

        // render an exception
        $this->handleException(new \Exception("This is not an error, you requested an exception to be thrown at the end of the script."));
    }
}
