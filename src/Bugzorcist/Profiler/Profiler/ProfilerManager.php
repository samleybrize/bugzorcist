<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\Profiler;

/**
 * Profiler manager
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ProfilerManager implements \Countable, \Iterator
{
    /**
     * Profilers list
     * @var \Bugzorcist\Profiler\Profiler\Profiler[]
     */
    protected $profilerList = array();

    /**
     * Adds a profiler
     * @param \Bugzorcist\Profiler\Profiler\Profiler $profiler
     */
    public function addProfiler(Profiler $profiler)
    {
        $this->profilerList[] = $profiler;
    }

    /**
     * Returns all profilers
     * @return \Bugzorcist\Profiler\Profiler\Profiler[]
     */
    public function getProfilers()
    {
        return $this->profilerList;
    }

    /**
     * Returns the number of profiler
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
