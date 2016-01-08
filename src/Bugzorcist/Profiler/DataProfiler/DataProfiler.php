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
 * Data profiler
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class DataProfiler implements \Countable, \Iterator
{
    /**
     * Data source name
     * @var string
     */
    private $dataSourceName;

    /**
     * Indicates if the profiler is enabled
     * @var boolean
     */
    private $enabled = true;

    /**
     * Profiles list
     * @var \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface[]
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
     * Query string formatter
     * @var \Bugzorcist\Profiler\DataProfiler\Formatter\FormatterInterface
     */
    private $formatter;

    /**
     * Base path of serialized profiles
     * @var string
     */
    private $path;

    /**
     * Constructor
     * @param string $dataSourceName data source name
     */
    public function __construct($dataSourceName)
    {
        $this->dataSourceName   = (string) $dataSourceName;
        $this->path             = sys_get_temp_dir() . "/" . uniqid($dataSourceName);
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
     * @param string $queryText query string
     * @param array $params [optional] query parameters
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfile
     */
    protected function createProfile($queryText, array $params = array())
    {
        return new Profile\DataProfile($queryText, $params);
    }

    /**
     * Returns data source name
     * @return string
     */
    public function getDataSourceName()
    {
        return $this->dataSourceName;
    }

    /**
     * Sets the query string formatter
     * @param \Bugzorcist\Profiler\DataProfiler\Formatter\FormatterInterface $formatter
     */
    public function setFormatter(Formatter\FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Returns the query string formatter
     * @return \Bugzorcist\Profiler\DataProfiler\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        if (null === $this->formatter) {
            // default formatter
            $this->formatter = new Formatter\FormatterDefault();
        }

        return $this->formatter;
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
     * Starts a new query profile
     * @param string $queryText query string
     * @param array $params [optional] query parameters
     * @return int profile identifier
     */
    public function startQuery($queryText, array $params = array())
    {
        if (!$this->enabled) {
            return;
        }

        $id                     = $this->profileNextId;
        $this->profileList[$id] = $this->createProfile($queryText, $params);
        $this->profileList[$id]->start();
        $this->profileNextId++;
        return $id;
    }

    /**
     * Stops a query profile
     * @param int $identifier [optional] identifier of the query profile to stop. If omitted, the last started query profile will be stopped.
     */
    public function stopQuery($identifier = null)
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
     * Returns a query profile
     * @param int $identifier query profile identifier
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface
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
     * Returns all query profiles
     * @return \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface[]
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
     * @return float
     */
    public function getTotalElapsedSecs()
    {
        return $this->totalElapsedSecs;
    }

    /**
     * Returns the number of query profiles
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
