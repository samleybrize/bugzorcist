<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\DataProfiler\ProfilerProxy;

use Doctrine\DBAL\Logging\SQLLogger;

use Bugzorcist\Profiler\DataProfiler\DataProfiler;

/**
 * Doctrine profiler proxy
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ProxyDoctrine implements SQLLogger
{
    /**
     * Proxied profiler
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfiler
     */
    protected $profilerProxied;

    /**
     * Constructor
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfiler $profilerProxied proxied profiler
     */
    public function __construct(DataProfiler $profilerProxied)
    {
        $this->profilerProxied = $profilerProxied;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->profilerProxied->startQuery($sql, (array) $params);
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $this->profilerProxied->stopQuery();
    }
}
