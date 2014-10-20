<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\DataProfiler\Profile;

interface DataProfileInterface
{
    /**
     * Constructor
     * @param string $query query string
     * @param array $params [optional] query params
     */
    public function __construct($queryText, array $params = array());

    /**
     * Starts profile
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface
     */
    public function start();

    /**
     * Ends profile
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface
     */
    public function end();

    /**
     * Adds a query param
     * @param string $param param name
     * @param mixed $variable param value
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface
     */
    public function bindParam($param, $variable);

    /**
     * Adds query params
     * @param array $params keys are param names, values are associated param values
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface
     */
    public function bindParams(array $params);

    /**
     * Indicates if the profile has ended
     * @return boolean
     */
    public function hasEnded();

    /**
     * Returns the query string
     * @return string
     */
    public function getQueryText();

    /**
     * Returns query params
     * @return array
     */
    public function getQueryParams();

    /**
     * Returns the profile execution time. If the profile has not ended, false is returned.
     * @return float|false
     */
    public function getElapsedSecs();

    /**
     * Returns the profile start UNIX timestamp
     * @return float
     */
    public function getStartMicrotime();

    /**
     * Returns the profile end UNIX timestamp. If the profile has not ended, false is returned.
     * @return float|false
     */
    public function getEndMicrotime();

    /**
     * Returns the profile start memory usage (in bytes)
     * @return int
     */
    public function getStartMemoryUsage($formatted = false);

    /**
     * Returns the profile end memory usage (in bytes). If the profile has not ended, false is returned.
     * @return int|false
     */
    public function getEndMemoryUsage($formatted = false);

    /**
     * Returns the profile start memory peak usage (in bytes)
     * @return int
     */
    public function getStartPeakMemoryUsage($formatted = false);

    /**
     * Returns the profile end memory peak usage (in bytes). If the profile has not ended, false is returned.
     * @return int|false
     */
    public function getEndPeakMemoryUsage($formatted = false);
}
