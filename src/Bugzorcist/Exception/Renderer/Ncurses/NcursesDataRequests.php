<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer\Ncurses;

use Bugzorcist\Profiler\DataProfiler\DataProfilerManager;

/**
 * Ncurses data requests viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesDataRequests extends NcursesVerticalCursorAbstract
{
    /**
     * Data profiler manager
     * @var \Bugzorcist\Profiler\DataProfiler\DataProfilerManager
     */
    private $dataProfilerManager;

    /**
     * Start time of the process
     * @var float
     */
    private $startMicrotime;

    /**
     * Constructor
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     * @param float $startMicrotime start time of the process
     * @param \Bugzorcist\Profiler\DataProfiler\DataProfilerManager $dataProfilerManager data profiler manager
     */
    public function __construct($padPositionX, $padPositionY, $startMicrotime, DataProfilerManager $dataProfilerManager = null)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->dataProfilerManager  = $dataProfilerManager;
        $this->startMicrotime       = $startMicrotime;

        if (null === $dataProfilerManager) {
            return;
        }

        foreach ($dataProfilerManager as $idProfiler => $profiler) {
            $this->addExpandableElement($idProfiler);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isShowable()
    {
        return $this->dataProfilerManager && count($this->dataProfilerManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        if (null === $this->dataProfilerManager || 0 == count($this->dataProfilerManager)) {
            return;
        }

        foreach ($this->dataProfilerManager as $idProfiler => $profiler) {
            // retrieve current position
            $x = null;
            $y = null;
            ncurses_getyx($this->getPadResource(), $y, $x);

            // print profiler name and total execution time for that profiler
            $totalTime  = count($profiler) ? $profiler->getTotalElapsedSecs() : 0;
            $totalTime  = number_format($totalTime, 4, ".", " ");
            $title      = $profiler->getDataSourceName() . " <<5>>(total execution time : $totalTime s)";
            $symbol     = $this->isElementExpanded($idProfiler) ? "▾" : "▸";
            ncurses_wattron($this->getPadResource(), NCURSES_A_BOLD);
            $this->printText("<<4>>$title <<4>>$symbol\n");
            ncurses_wattroff($this->getPadResource(), NCURSES_A_BOLD);

            $this->setExpandableElementPositionY($idProfiler, $y);

            if (!$this->isElementExpanded($idProfiler)) {
                continue;
            }

            // print profiles
            if (count($profiler)) {
                foreach ($profiler as $i => $query) {
                    $this->printText("    ");
                    ncurses_wattron($this->getPadResource(), NCURSES_A_REVERSE);
                    $i = str_pad($i, 6, " ", STR_PAD_RIGHT);
                    $this->printText("<<6>>#$i\n");
                    ncurses_wattroff($this->getPadResource(), NCURSES_A_REVERSE);

                    // print query
                    $queryText      = $profiler->getFormatter()->formatPlain($query->getQueryText());
                    $queryText      = str_replace(array("\r\n", "\r"), "\n", $queryText);
                    $queryText      = str_replace("\t", "    ", $queryText);
                    $queryText      = explode("\n", $queryText);

                    foreach ($queryText as $queryLine) {
                        $this->printText("        $queryLine\n");
                    }

                    $this->printText(" \n");

                    // query params
                    if ($query->getQueryParams()) {
                        $maxLength = 0;

                        foreach ($query->getQueryParams() as $paramName => $paramValue) {
                            $maxLength = max($maxLength, strlen($paramName));
                        }

                        foreach ($query->getQueryParams() as $paramName => $paramValue) {
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
                                default:
                                    $paramValue = "";
                            }

                            $paramName  = str_pad($paramName, $maxLength, " ", STR_PAD_RIGHT);
                            $this->printText("        <<5>>$paramName => <<2>>($paramType) <<5>>$paramValue\n");
                        }

                        $this->printText(" \n");
                    }

                    // query stats
                    $startTime          = $query->getStartMicrotime() - $this->startMicrotime;
                    $endTime            = $query->getEndMicrotime() ? $query->getEndMicrotime() - $this->startMicrotime : 0;
                    $queryTime          = $this->formatQueryTime($startTime, $endTime);

                    $startMemory        = $query->getStartMemoryUsage(true);
                    $endMemory          = $query->getEndMemoryUsage(true);
                    $startPeakMemory    = $query->getStartPeakMemoryUsage(true);
                    $endPeakMemory      = $query->getEndPeakMemoryUsage(true);
                    $queryMemory        = $this->formatQueryMemory($startMemory, $endMemory, "Memory usage");
                    $queryPeakMemory    = $this->formatQueryMemory($startPeakMemory, $endPeakMemory, "Memory peak usage");

                    $queryStats         = array(
                        "{$queryTime[0]} | {$queryMemory[0]} | {$queryPeakMemory[0]}",
                        "{$queryTime[1]} | {$queryMemory[1]} | {$queryPeakMemory[1]}",
                        "{$queryTime[2]} | {$queryMemory[2]} | {$queryPeakMemory[2]}",
                        "{$queryTime[3]} |",
                    );

                    foreach ($queryStats as $statLine) {
                        $this->printText("        $statLine\n");
                    }

                    $this->printText(" \n");
                }
            }
        }
    }

    /**
     * Format query time stats
     * @param float $startTime start time of the query, relative to the start time of the process
     * @param float $endTime end time of the query, relative to the start time of the process
     * @return array
     */
    protected function formatQueryTime($startTime, $endTime)
    {
        $execTime   = $endTime - $startTime;

        // pad values
        $startTime  = number_format($startTime, 4, ".", " ");
        $endTime    = number_format($endTime, 4, ".", " ");
        $execTime   = number_format($execTime, 4, ".", " ");

        $maxLength  = max(
            strlen($startTime),
            strlen($endTime),
            strlen($execTime)
        );

        $startTime  = str_pad($startTime, $maxLength, " ", STR_PAD_LEFT);
        $endTime    = str_pad($endTime, $maxLength, " ", STR_PAD_LEFT);
        $execTime   = str_pad($execTime, $maxLength, " ", STR_PAD_LEFT);

        // pad labels
        $labelStart = "Started at";
        $labelEnd   = "Ended at";
        $labelExec  = "Execution time";
        $labelTitle = "Execution time";

        $maxLength  = max(
            strlen($labelStart) + strlen($startTime) + 5,
            strlen($labelEnd) + strlen($endTime) + 5,
            strlen($labelExec) + strlen($execTime) + 5,
            strlen($labelTitle)
        );

        $labelStart = str_pad($labelStart, $maxLength - strlen($startTime) - 5, " ", STR_PAD_RIGHT);
        $labelEnd   = str_pad($labelEnd, $maxLength - strlen($endTime) - 5, " ", STR_PAD_RIGHT);
        $labelExec  = str_pad($labelExec, $maxLength - strlen($execTime) - 5, " ", STR_PAD_RIGHT);

        $titlePad   = $maxLength - strlen($labelTitle);
        $labelTitle = str_repeat(" ", $titlePad / 2) . $labelTitle . str_repeat(" ", ceil($titlePad / 2));

        return array(
            $labelTitle,
            "<<3>>$labelStart   <<0>>$startTime s",
            "<<3>>$labelEnd   <<0>>$endTime s",
            "<<3>>$labelExec   <<0>>$execTime s"
        );
    }

    /**
     * Format query memory stats
     * @param string $startMemory memory usage right before the query
     * @param string $endMemory memory usage right after the query
     * @param string $labelTitle title
     * @return array
     */
    protected function formatQueryMemory($startMemory, $endMemory, $labelTitle)
    {
        // pad values
        $maxLength  = max(
            strlen($startMemory),
            strlen($endMemory)
        );

        $startMemory    = str_pad($startMemory, $maxLength, " ", STR_PAD_LEFT);
        $endMemory      = str_pad($endMemory, $maxLength, " ", STR_PAD_LEFT);
        $labelTitle     = str_pad($labelTitle, $maxLength, " ", STR_PAD_LEFT);

        // pad labels
        $labelStart = "Start";
        $labelEnd   = "End";

        $maxLength  = max(
            strlen($labelStart) + strlen($startMemory) + 3,
            strlen($labelEnd) + strlen($endMemory) + 3,
            strlen($labelTitle)
        );

        $labelStart = str_pad($labelStart, $maxLength - strlen($startMemory) - 3, " ", STR_PAD_RIGHT);
        $labelEnd   = str_pad($labelEnd, $maxLength - strlen($endMemory) - 3, " ", STR_PAD_RIGHT);

        $titlePad   = $maxLength - strlen($labelTitle);
        $labelTitle = str_repeat(" ", $titlePad / 2) . $labelTitle . str_repeat(" ", ceil($titlePad / 2));

        return array(
            $labelTitle,
            "<<3>>$labelStart   <<0>>$startMemory",
            "<<3>>$labelEnd   <<0>>$endMemory",
        );
    }
}
