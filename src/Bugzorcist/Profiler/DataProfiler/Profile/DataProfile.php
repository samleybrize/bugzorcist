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

use Bugzorcist\Utils\UnitFormatter;

class DataProfile implements DataProfileInterface
{
    /**
     * Query string
     * @var string
     */
    protected $queryText;

    /**
     * Profile start UNIX timestamp
     * @var float
     */
    protected $startMicrotime;

    /**
     * Profile end UNIX timestamp
     * @var float
     */
    protected $endMicrotime;

    /**
     * Profile start memory usage (in bytes)
     * @var int
     */
    protected $startMemoryUsage;

    /**
     * Profile end memory usage (in bytes)
     * @var int
     */
    protected $endMemoryUsage;

    /**
     * Profile start memory peak usage (in bytes)
     * @var int
     */
    protected $startPeakMemoryUsage;

    /**
     * Profile end memory peak usage (in bytes)
     * @var int
     */
    protected $endPeakMemoryUsage;

    /**
     * Query params
     * @var array
     */
    protected $params = array();

    /**
     * @see \Bugzorcist\Profiler\DataProfiler\Profile\DataProfileInterface::__construct()
     */
    public function __construct($queryText, array $params = array())
    {
        $this->queryText    = (string) $queryText;
        $this->params       = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if (null !== $this->startMemoryUsage) {
            return;
        }

        $this->startMemoryUsage     = memory_get_usage(true);
        $this->endMemoryUsage       = null;
        $this->startPeakMemoryUsage = memory_get_peak_usage(true);
        $this->endPeakMemoryUsage   = null;
        $this->startMicrotime       = microtime(true);
        $this->endMicrotime         = null;
    }

    /**
     * {@inheritdoc}
     */
    public function end()
    {
        if ($this->hasEnded()) {
            return;
        }

        $this->endMicrotime         = microtime(true);
        $this->endMemoryUsage       = memory_get_usage(true);
        $this->endPeakMemoryUsage   = memory_get_peak_usage(true);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, $variable)
    {
        $this->params[$param] = $variable;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParams(array $params)
    {
        foreach ($params as $param => $value) {
            $this->bindParam($param, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasEnded()
    {
        return $this->endMicrotime !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function getElapsedSecs()
    {
        if (null === $this->endMicrotime) {
            return false;
        }

        return $this->endMicrotime - $this->startMicrotime;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartMicrotime()
    {
        return $this->startMicrotime;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndMicrotime()
    {
        return $this->endMicrotime ? $this->endMicrotime : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartMemoryUsage($formatted = false)
    {
        if ($formatted) {
            return UnitFormatter::formatByte($this->startMemoryUsage);
        } else {
            return $this->startMemoryUsage;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEndMemoryUsage($formatted = false)
    {
        if ($formatted && $this->endMemoryUsage) {
            return UnitFormatter::formatByte($this->endMemoryUsage);
        } elseif ($this->endMemoryUsage) {
            return $this->endMemoryUsage;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStartPeakMemoryUsage($formatted = false)
    {
        if ($formatted) {
            return UnitFormatter::formatByte($this->startPeakMemoryUsage);
        } else {
            return $this->startPeakMemoryUsage;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEndPeakMemoryUsage($formatted = false)
    {
        if ($formatted && $this->endPeakMemoryUsage) {
            return UnitFormatter::formatByte($this->endPeakMemoryUsage);
        } elseif ($this->endPeakMemoryUsage) {
            return $this->endPeakMemoryUsage;
        } else {
            return false;
        }
    }
}
