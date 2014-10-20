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

abstract class ExceptionRendererAbstract
{
    /**
     * Exception to render
     * @var \Exception
     */
    private $exception;

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
     * UNIX timestamp at the construction of this object
     * @var float
     */
    private $microtime;

    /**
     * Memory usage (in bytes) at the construction of this object
     * @var int
     */
    private $memoryUsage;

    /**
     * Memory peak usage (in bytes) at the construction of this object
     * @var int
     */
    private $memoryPeakUsage;

    /**
     * Constructor
     * @param \Exception $e exception to render
     */
    public function __construct(\Exception $e)
    {
        $this->exception        = $e;
        $this->microtime        = microtime(true);
        $this->memoryUsage      = memory_get_usage(true);
        $this->memoryPeakUsage  = memory_get_peak_usage(true);
    }

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
     * Returns the exception
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Returns the application config
     * @return mixed
     */
    public function getApplicationConfig()
    {
        return $this->applicationConfig;
    }

    /**
     * Returns the profiler manager
     * @return \Bugzorcist\Profiler\Profiler\ProfilerManager|null
     */
    public function getProfilerManager()
    {
        return $this->profilerManager;
    }

    /**
     * Returns the data profiler manager
     * @return \Bugzorcist\Profiler\DataProfiler\DataProfilerManager|null
     */
    public function getDataProfilerManager()
    {
        return $this->dataProfilerManager;
    }

    /**
     * Returns the UNIX timestamp at the construction of this object
     * @return float
     */
    protected function getMicrotime()
    {
        return $this->microtime;
    }

    /**
     * Returns the memory usage (in bytes) at the construction of this object
     * @return int
     */
    protected function getMemoryUsage()
    {
        return $this->memoryUsage;
    }

    /**
     * Returns the memory peak usage (in bytes) at the construction of this object
     * @return int
     */
    protected function getMemoryPeakUsage()
    {
        return $this->memoryPeakUsage;
    }

    /**
     * Renders exception
     */
    abstract public function render();
}
