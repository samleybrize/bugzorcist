<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\Profiler\Profile;

interface ProfileInterface
{
    /**
     * Constructor
     * @param array $params [optional] params
     * @param string $comment [optional] comment
     */
    public function __construct(array $params = array(), $comment = null);

    /**
     * Starts profile
     * Cette méthode peut être appelée plusieurs fois pour réinitialiser la mesure, et est appelée automatiquement par le constructeur.
     */
    public function start();

    /**
     * Ends profile
     */
    public function end();

    /**
     * Indicates if the profile has ended
     * @return boolean
     */
    public function hasEnded();

    /**
     * Gets the function name in which the profile has started
     * @return string
     */
    public function getCallingFunction();

    /**
     * Gets the file in which the profile has started
     * @return string
     */
    public function getCallingFile();

    /**
     * Gets the line number at which the profile has started
     * @return string
     */
    public function getCallingLine();

    /**
     * Gets the stack trace at the profile start
     * @return array
     */
    public function getCallingTrace();

    /**
     * Returns params
     * @return array
     */
    public function getParams();

    /**
     * Returns the comment
     * @return string
     */
    public function getComment();

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
