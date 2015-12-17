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
 * Profiler
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class Profiler implements \Countable, \Iterator
{
    /**
     * Profiler name
     * @var string
     */
    private $profilerName;

    /**
     * Indicates if the profiler is enabled
     * @var boolean
     */
    private $enabled = true;

    /**
     * Profiles list
     * @var \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface[]
     */
    private $profileList = array();

    /**
     * Next profile identifier
     * @var int
     */
    private $profileNextId = 0;

    /**
     * Cumulated execution time of all profiles
     * @var float
     */
    private $totalElapsedSecs = 0.0;

    /**
     * Base path of serialized profiles
     * @var string
     */
    private $path;

    /**
     * Constructor
     * @param string $profilerName profiler name
     */
    public function __construct($profilerName)
    {
        $this->profilerName = (string) $profilerName;
        $this->path         = sys_get_temp_dir() . "/" . uniqid($profilerName);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        foreach ($this->profileList as $path) {
            if (!is_string($path)) {
                continue;
            }

            @unlink($path);
        }
    }

    /**
     * Creates a new profile
     * @param array $params [optional] parameters
     * @param string $comment [optional] comment
     * @return \Bugzorcist\Profiler\Profiler\Profile\Profile
     */
    protected function createProfile(array $params = array(), $comment = null)
    {
        return new Profile\Profile($params, $comment);
    }

    /**
     * Returns profiler name
     * @return string
     */
    public function getProfilerName()
    {
        return $this->profilerName;
    }

    /**
     * Enable/disable profiler
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;
    }

    /**
     * Indicates if the profiler is enabled
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Starts a new profile
     * @param array $params [optional] parameters
     * @param string $comment [optional] comment
     * @return int profile identifier
     */
    public function startProfile(array $params = array(), $comment = null)
    {
        if (!$this->enabled) {
            return;
        }

        $id                     = $this->profileNextId;
        $this->profileList[$id] = $this->createProfile($params, $comment);
        $this->profileList[$id]->start();
        $this->profileNextId++;
        return $id;
    }

    /**
     * Stops a profile
     * @param int $identifier [optional] identifier of the profile to stop. If omitted, the last started profile will be stopped.
     */
    public function stopProfile($identifier = null)
    {
        if (!$this->enabled) {
            return;
        }

        // if no identifier given, take the last started profile
        if (null === $identifier) {
            $identifier = $this->profileNextId - 1;
        }

        // get the profile
        if (array_key_exists($identifier, $this->profileList)) {
            $profile = $this->profileList[$identifier];
        } else {
            // asked profile does not exists
            return;
        }

        // stop profile
        if ($profile && !is_string($profile) && !$profile->hasEnded()) {
            $profile->end();
            $this->totalElapsedSecs += $profile->getElapsedSecs();

            // profile is cloned to workaround a side effect of the serialize function
            // it find the profile object in $this->profileList and serialize as a reference of it, resulting in "r:2;"
            $profile = clone $profile;

            // the profile is serialised into a temporary file
            // this minimizes the overhead of holding many profiles into the heap
            file_put_contents($this->path . $identifier, serialize($profile));
            $this->profileList[$identifier] = $this->path . $identifier;
        }
    }

    /**
     * Returns a profile
     * @param int $identifier
     * @return \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface
     */
    public function getProfile($identifier)
    {
        if (array_key_exists($identifier, $this->profileList)) {
            return is_string($this->profileList[$identifier]) ?
                unserialize(file_get_contents($this->profileList[$identifier])) :
                $this->profileList[$identifier]
            ;
        }
    }

    /**
     * Returns all profiles
     * @return \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface[]
     */
    public function getProfiles()
    {
        $profiles = array();

        foreach ($this->profileList as $identifier => $v) {
            $profiles[$identifier] = $this->getProfile($identifier);
        }

        return $profiles;
    }

    /**
     * Returns the cumulated execution time of all query profiles
     * @return int
     */
    public function getTotalElapsedSecs()
    {
        return $this->totalElapsedSecs;
    }

    /**
     * Returns the number of profiles
     * @return int
     */
    public function count()
    {
        return count($this->profileList);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->getProfile($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->profileList);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->profileList);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->profileList);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return null !== $this->key();
    }
}
