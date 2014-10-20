<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\DataProfiler;

/**
 * Data profiler manager
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class DataProfilerManager implements \Countable, \Iterator
{
    /**
     * Profilers list
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfiler[]
     */
    private $profilerList = array();

    /**
     * Adds a profiler
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfiler $profiler
     */
    public function addProfiler(DataProfiler $profiler)
    {
        $this->profilerList[] = $profiler;
    }

    /**
     * Returns all profilers
     * @return \Bugzorcist\Profiler\DataProfiler\DataProfiler[]
     */
    public function getProfilers()
    {
        return $this->profilerList;
    }

    /**
     * Returns the number of profilers
     * @return int
     */
    public function count()
    {
        return count($this->profilerList);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->profilerList);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->profilerList);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->profilerList);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->profilerList);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return false !== $this->current();
    }
}
