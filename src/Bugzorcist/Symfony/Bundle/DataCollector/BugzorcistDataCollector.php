<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Symfony\Bundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\KernelInterface;

use Bugzorcist\Profiler\DataProfiler\DataProfilerManager;

/**
 * BugzorcistDataCollector
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class BugzorcistDataCollector extends DataCollector
{
    /**
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfilerManager
     */
    private $dataProfilerManager;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;

    /**
     * Constructor
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfilerManager $dataProfilerManager
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(DataProfilerManager $dataProfilerManager, KernelInterface $kernel)
    {
        $this->dataProfilerManager  = $dataProfilerManager;
        $this->kernel               = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $queryCount     = 0;
        $queryTime      = 0;
        $dataSources    = array();

        foreach ($this->dataProfilerManager as $profiler) {
            $queryCount        += $profiler->count();
            $queryTime         += $profiler->getTotalElapsedSecs();

            $name               = $profiler->getDataSourceName();
            $dataSources[$name] = array(
                "queryTime"         => $profiler->getTotalElapsedSecs(),
                "queryCount"        => $profiler->count(),
                "profiles"          => array()
            );

            foreach ($profiler as $profile) {
                $dataSources[$name]["profiles"][] = array(
                    "queryTime"             => $profile->hasEnded() ? $profile->getElapsedSecs() : false,
                    "queryText"             => $profiler->getFormatter()->formatHtml($profile->getQueryText()),
                    "params"                => $this->formatQueryParams($profile->getQueryParams()),
                    "startTime"             => $profile->getStartMicrotime() - $this->kernel->getStartTime(),
                    "endTime"               => $profile->hasEnded() ? $profile->getEndMicrotime() - $this->kernel->getStartTime() : false,
                    "startMemoryUsage"      => $profile->getStartMemoryUsage(true),
                    "endMemoryUsage"        => $profile->hasEnded() ? $profile->getEndMemoryUsage(true) : false,
                    "startPeakMemoryUsage"  => $profile->getStartPeakMemoryUsage(true),
                    "endPeakMemoryUsage"    => $profile->hasEnded() ? $profile->getEndPeakMemoryUsage(true) : false,
                );
            }
        }

        $this->data["queryCount"]   = $queryCount;
        $this->data["queryTime"]    = $queryTime;
        $this->data["dataSources"]  = $dataSources;
    }

    /**
     * Returns the total number of queries
     * @return int
     */
    public function queryCount()
    {
        return $this->data["queryCount"];
    }

    /**
     * Returns the total execution time of all queries
     * @return float
     */
    public function queryTime()
    {
        return $this->data["queryTime"];
    }

    /**
     * Returns data sources
     * @return array
     */
    public function dataSources()
    {
        return $this->data["dataSources"];
    }

    /**
     * Format query params
     * @param array $queryParams
     * @return array
     */
    private function formatQueryParams(array $queryParams)
    {
        $params = array();

        if (empty($queryParams)) {
            return $params;
        }

        // look for the longest parameter name
        $maxKeyLength = 0;

        foreach ($queryParams as $paramName => $paramValue) {
            $maxKeyLength = strlen($paramName) > $maxKeyLength ? strlen($paramName) : $maxKeyLength;
        }

        // build parameters
        foreach ($queryParams as $paramName => $paramValue) {
            $paramType  = "object" == gettype($paramValue) ? get_class($paramValue) : gettype($paramValue);

            switch ($paramType){
                case "string":
                case "integer":
                case "long":
                case "float":
                case "double":
                    // leave value as is
                    break;

                case "bool":
                case "boolean":
                    $paramValue = $paramValue ? "true" : "false";
                    break;

                case "null":
                case "NULL":
                case "array":
                case "object":
                default:
                    $paramValue = "";
            }

            $params[] = array(
                "name"      => str_pad($paramName, $maxKeyLength, " ", STR_PAD_RIGHT),
                "type"      => $paramType,
                "value"     => $paramValue
            );
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    function getName()
    {
        return "bugzorcist-data-source";
    }
}
