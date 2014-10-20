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

use Bugzorcist\Utils\UnitFormatter;

class Profile implements ProfileInterface
{
    /**
     * Function name in which the profile has started
     * @var string
     */
    protected $callingFunction;

    /**
     * File in which the profile has started
     * @var string
     */
    protected $callingFile;

    /**
     * Line number at which the profile has started
     * @var string
     */
    protected $callingLine;

    /**
     * Stack trace at the profile start
     * @var array
     */
    protected $callingTrace = array();

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
     * Params
     * @var array
     */
    protected $params = array();

    /**
     * Comment
     * @var string
     */
    protected $comment;

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::__construct()
     */
    public function __construct(array $params = array(), $comment = null)
    {
        $this->params   = $params;
        $this->comment  = $comment;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::start()
     */
    public function start()
    {
        if (null !== $this->startMemoryUsage) {
            return;
        }

        $trace                  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->callingTrace     = array();
        $this->callingFile      = null;
        $this->callingLine      = null;
        $this->callingFunction  = "{main}";

        foreach ($trace as $t) {
            if (array_key_exists("class", $t) &&
                    ("Bugzorcist\Profiler\Profiler\Profile\Profile" == $t["class"] || "Bugzorcist\Profiler\Profiler\Profiler" == $t["class"])) {
                // on retient le fichier et la ligne d'où a été demarré le profilage
                $this->callingFile = array_key_exists("file", $t) ? $t["file"] : "-";
                $this->callingLine = array_key_exists("line", $t) ? $t["line"] : "-";

                continue;
            } elseif ("{main}" == $this->callingFunction) {
                // on retient le nom de la fonction d'où a été demarré le profilage
                $this->callingFunction = array_key_exists("class", $t) ? "{$t['class']}{$t['type']}{$t['function']}()" : "{$t['function']}()";
            }

            $this->callingTrace[] = array(
                "func"  => array_key_exists("class", $t) ? "{$t['class']}{$t['type']}{$t['function']}()" : "{$t['function']}()",
                "file"  => array_key_exists("file", $t) ? $t["file"] : "-",
                "line"  => array_key_exists("line", $t) ? $t["line"] : "-",
            );
        }

        $this->startMemoryUsage     = memory_get_usage(true);
        $this->endMemoryUsage       = null;
        $this->startPeakMemoryUsage = memory_get_peak_usage(true);
        $this->endPeakMemoryUsage   = null;
        $this->startMicrotime       = microtime(true);
        $this->endMicrotime         = null;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::end()
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
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::hasEnded()
     */
    public function hasEnded()
    {
        return $this->endMicrotime !== null;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getCallingFunction()
     */
    public function getCallingFunction()
    {
        return $this->callingFunction;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getCallingFile()
     */
    public function getCallingFile()
    {
        return $this->callingFile;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getCallingLine()
     */
    public function getCallingLine()
    {
        return $this->callingLine;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getCallingTrace()
     */
    public function getCallingTrace()
    {
        return $this->callingTrace;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getParams()
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getComment()
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getElapsedSecs()
     */
    public function getElapsedSecs()
    {
        if (null === $this->endMicrotime) {
            return false;
        }

        return $this->endMicrotime - $this->startMicrotime;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getStartMicrotime()
     */
    public function getStartMicrotime()
    {
        return $this->startMicrotime;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getEndMicrotime()
     */
    public function getEndMicrotime()
    {
        return (null !== $this->endMicrotime) ? $this->endMicrotime : false;
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getStartMemoryUsage()
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
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getEndMemoryUsage()
     */
    public function getEndMemoryUsage($formatted = false)
    {
        if ($formatted && $this->endMemoryUsage) {
            return UnitFormatter::formatByte($this->endMemoryUsage);
        } elseif (null !== $this->endMemoryUsage) {
            return $this->endMemoryUsage;
        } else {
            return false;
        }
    }

    /**
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getStartPeakMemoryUsage()
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
     * @see \Bugzorcist\Profiler\Profiler\Profile\ProfileInterface::getEndPeakMemoryUsage()
     */
    public function getEndPeakMemoryUsage($formatted = false)
    {
        if ($formatted && $this->endPeakMemoryUsage) {
            return UnitFormatter::formatByte($this->endPeakMemoryUsage);
        } elseif (null !== $this->endPeakMemoryUsage) {
            return $this->endPeakMemoryUsage;
        } else {
            return false;
        }
    }
}
