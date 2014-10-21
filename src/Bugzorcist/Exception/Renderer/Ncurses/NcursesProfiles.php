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

use Bugzorcist\Profiler\Profiler\ProfilerManager;
use Bugzorcist\VarDump\VarDumpNcurses;

/**
 * Ncurses profiles viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesProfiles extends NcursesVerticalCursorAbstract
{
    /**
     * Profiler manager
     * @var \Bugzorcist\Profiler\Profiler\ProfilerManager
     */
    private $profilerManager;

    /**
     * Start time of the process
     * @var float
     */
    private $startMicrotime;

    /**
     * Link each profile param set to its Y position
     * @var array
     */
    private $profileParamsList = array();

    /**
     * Constructor
     * @param int $padPositionX x position of the pad in the main screen
     * @param int $padPositionY y position of the pad in the main screen
     * @param float $startMicrotime start time of the process
     * @param \Bugzorcist\Profiler\Profiler\ProfilerManager $profilerManager profiler manager
     */
    public function __construct($padPositionX, $padPositionY, $startMicrotime, ProfilerManager $profilerManager = null)
    {
        parent::__construct($padPositionX, $padPositionY);
        $this->profilerManager  = $profilerManager;
        $this->startMicrotime   = $startMicrotime;

        foreach ($this->profilerManager as $idProfiler => $profiler) {
            $this->addExpandableElement($idProfiler);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isShowable()
    {
        return $this->profilerManager && count($this->profilerManager);
    }

    /**
     * {@inheritdoc}
     */
    public function onKeyPress($keyCode)
    {
        switch ($keyCode) {
            // enter key
            case 13:
                // show profile params
                if (array_key_exists($this->getCursorPositionY(), $this->profileParamsList)) {
                    $params = $this->profileParamsList[$this->getCursorPositionY()];
                    VarDumpNcurses::dump($params, "Params", false);
                    return true;
                } else {
                    parent::onKeyPress($keyCode);
                }
                break;

            default:
                parent::onKeyPress($keyCode);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        if (null === $this->profilerManager || 0 == count($this->profilerManager)) {
            return;
        }

        $this->profileParamsList = array();

        foreach ($this->profilerManager as $idProfiler => $profiler) {
            // retrieve current position
            $x = null;
            $y = null;
            ncurses_getyx($this->getPadResource(), $y, $x);

            // print profiler name and total execution time for that profiler
            $totalTime  = count($profiler) ? $profiler->getTotalElapsedSecs() : 0;
            $totalTime  = number_format($totalTime, 4, ".", " ");
            $title      = $profiler->getProfilerName() . " <<5>>(total execution time : $totalTime s)";
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
                foreach ($profiler as $i => $profile) {
                    $funcName   = $profile->getCallingFunction();
                    $file       = $profile->getCallingFile();
                    $line       = $profile->getCallingLine();
                    $comment    = $profile->getComment();

                    $this->printText("    ");
                    ncurses_wattron($this->getPadResource(), NCURSES_A_REVERSE);
                    $this->printText("<<6>>#$i $funcName\n");
                    ncurses_wattroff($this->getPadResource(), NCURSES_A_REVERSE);

                    // base infos
                    $this->printText("        <<2>>Start file : <<0>>$file\n");
                    $this->printText("        <<2>>Start line : <<0>>$line\n");

                    if ($comment) {
                        $this->printText("        <<2>>Comment    : <<0>>$comment\n");
                    }

                    $this->printText(" \n");

                    // args
                    $x = null;
                    $y = null;
                    ncurses_getyx($this->getPadResource(), $y, $x);

                    $this->printText("        <<4>>Params ▸\n");
                    $this->printText(" \n");

                    $this->profileParamsList[$y] = $profile->getParams();

                    // query stats
                    $startTime          = $profile->getStartMicrotime() - $this->startMicrotime;
                    $endTime            = $profile->getEndMicrotime() ? $profile->getEndMicrotime() - $this->startMicrotime : 0;
                    $profileTime        = $this->formatQueryTime($startTime, $endTime);

                    $startMemory        = $profile->getStartMemoryUsage(true);
                    $endMemory          = $profile->getEndMemoryUsage(true);
                    $startPeakMemory    = $profile->getStartPeakMemoryUsage(true);
                    $endPeakMemory      = $profile->getEndPeakMemoryUsage(true);
                    $profileMemory      = $this->formatQueryMemory($startMemory, $endMemory, "Memory usage");
                    $profilePeakMemory  = $this->formatQueryMemory($startPeakMemory, $endPeakMemory, "Memory peak usage");

                    $profileStats       = array(
                        "{$profileTime[0]} | {$profileMemory[0]} | {$profilePeakMemory[0]}",
                        "{$profileTime[1]} | {$profileMemory[1]} | {$profilePeakMemory[1]}",
                        "{$profileTime[2]} | {$profileMemory[2]} | {$profilePeakMemory[2]}",
                        "{$profileTime[3]} |",
                    );

                    foreach ($profileStats as $statLine) {
                        $this->printText("        $statLine\n");
                    }

                    $this->printText(" \n");

                    // stack trace
                    $stackTrace = $profile->getCallingTrace();
                    $num        = count($stackTrace) - 1;
                    $numLength  = strlen($num);

                    foreach ($stackTrace as $event) {
                        $numString  = str_pad($num, $numLength, "0", STR_PAD_LEFT);
                        $this->printText("        <<0>>#$numString <<1>>{$event['func']}\n");
                        $this->printText("            <<2>>File :<<0>> {$event['file']}\n");
                        $this->printText("            <<2>>Line :<<0>> {$event['line']}\n");
                        $this->printText(" \n");
                        $num--;
                    }
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
