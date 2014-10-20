<?php

namespace Bugzorcist\Exception\Renderer;

use Bugzorcist\Utils\UnitFormatter;

class ExceptionRendererFirephp extends ExceptionRendererAbstract
{
    /**
     * @var \FirePHP
     */
    protected $firePhp;

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if (null === $this->firePhp) {
            $this->firePhp = \FirePHP::getInstance(true);
        }

        $exception  = $this->getException();
        $message    = $exception->getMessage();
        $file       = $exception->getFile();
        $line       = $exception->getLine();
        $code       = $exception->getCode();
        $host       = "";
        $uri        = "";
        $first      = true;

        if (array_key_exists("HTTP_HOST", $_SERVER)) {
            $host = $_SERVER['HTTP_HOST'];
        }

        if (array_key_exists("REQUEST_METHOD", $_SERVER) && array_key_exists("REQUEST_URI", $_SERVER)) {
            $uri = "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}";
        }

        while ($exception instanceof \Exception) {
            // create container group
            $this->firePhp->group(get_class($exception), array("Collapsed" => true, "Color" => "red"));

            // show base informations
            $this->firePhp->log($message);
            $this->firePhp->log("FILE : $file");
            $this->firePhp->log("LINE : $line");
            $this->firePhp->log("CODE : $code");

            if ($host) {
                $this->firePhp->log("HOST : $host");
            }

            if ($uri) {
                $this->firePhp->log("URI  : $uri");
            }

            // show details
            $this->showStackTrace($exception);

            if ($first) {
                // only the first exception has all details
                $this->showRequest();
                $this->showResponse();
                $this->showConstants();
                $this->showUserConstants();
                $this->showDataRequests();
                $this->showProfiles();
                $this->showApplicationConfig();
                $this->showPhpConfig();
                $this->showIncludedFiles();
                $this->showLoadedExtensions();
                $this->showMemoryUsage();
                $this->showExecutionTime();
            }

            $this->firePhp->groupEnd();

            $exception  = $exception->getPrevious();
            $first      = false;
        }
    }

    /**
     * Opens a category
     * @param string $title category title
     */
    protected function openCat($title)
    {
        $this->firePhp->group($title, array("Collapsed" => true, "Color" => "blue"));
    }

    /**
     * Closes the last opened category
     */
    protected function closeCat()
    {
        $this->firePhp->groupEnd();
    }

    /**
     * Shows stack trace
     * @param \Exception $exception
     */
    protected function showStackTrace(\Exception $exception)
    {
        $trace      = $exception->getTrace();
        $count      = count($trace);
        $digits     = strlen(max(count($trace) - 1, 0));
        $table      = array();
        $table[]    = array("", "Function", "Line", "File", "Args");
        $this->openCat("StackTrace");

        if ($trace) {
            // show stack trace
            foreach ($trace as $k => $v) {
                // format line
                $index      = str_pad($count - 1 - $k, $digits, "0", STR_PAD_LEFT);

                if (array_key_exists("main", $v)) {
                    $function = "{main}";
                } else {
                    $function = array_key_exists("class", $v) ? "{$v['class']}{$v['type']}{$v['function']}()" : "{$v['function']}()";
                }

                $file       = array_key_exists("file", $v) ? $v["file"] : null;
                $line       = array_key_exists("line", $v) ? $v["line"] : null;
                $table[]    = array($index, $function, $line, $file, $v['args']);
            }

            $this->firePhp->table("Show", $table);
        } else {
            // empty stack trace
            $this->firePhp->log("* empty *");
        }

        $this->closeCat();
    }

    /**
     * Shows included files
     */
    protected function showIncludedFiles()
    {
        $files = get_included_files();
        $this->openCat("Included Files");
        $this->firePhp->log(count($files) . " files");

        foreach ($files as $k => $v) {
            $this->firePhp->log($v);
        }

        $this->closeCat();
    }

    /**
     * Shows HTTP request
     */
    protected function showRequest()
    {
        $this->openCat("Request");
        $vars = array(
            '$_GET'     => $_GET,
            '$_POST'    => $_POST,
            '$_SERVER'  => $_SERVER,
            '$_ENV'     => $_ENV
        );

        foreach ($vars as $varName => $values) {
            $table = array();

            if (!empty($values)) {
                // show a key/values table
                $table[] = array("Key", "Value");

                foreach ($values as $k => $v) {
                    $table[] = array($k, $v);
                }
            } else {
                // nothing to display
                $table[] = array("* empty *");
            }

            $this->firePhp->table($varName, $table);
        }

        $this->closeCat();
    }

    /**
     * Shows HTTP response
     */
    protected function showResponse()
    {
        $headers = headers_list();
        $this->openCat("Response");

        foreach ($headers as $k => $v) {
            // firephp headers are not displayed
            if (strpos($v, "X-Wf-") === 0) {
                continue;
            }

            $this->firePhp->log($v);
        }

        $this->closeCat();
    }

    /**
     * Shows memory usage
     */
    protected function showMemoryUsage()
    {
        $memory       = UnitFormatter::formatByte($this->getMemoryUsage());
        $memoryPeak   = UnitFormatter::formatByte($this->getMemoryPeakUsage());
        $memoryLimit  = ini_get("memory_limit");

        if (-1 == $memoryLimit) {
            $memoryLimit = "-1 (no limit)";
        }

        $this->openCat("Memory Usage");
        $this->firePhp->log("Memory used : $memory");
        $this->firePhp->log("Memory peak : $memoryPeak");
        $this->firePhp->log("Memory limit : $memoryLimit");
        $this->closeCat();
    }

    /**
     * Shows execution time
     */
    protected function showExecutionTime()
    {
        $time = $this->getMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];

        $this->openCat("Execution Time");
        $this->firePhp->log("$time s");
        $this->closeCat();
    }

    /**
     * Shows constants
     */
    protected function showConstants()
    {
        $constants = get_defined_constants(true);
        unset($constants["user"]);
        unset($constants["standard"]["INF"]); // infinite is not supported by JSON
        unset($constants["standard"]["NAN"]); // not supported by JSON

        $this->openCat("Constants");

        foreach ($constants as $group => $values) {
            if (!empty($values)) {
                // show a key/values table
                $table      = array();
                $table[]    = array("Constant", "Value");

                foreach ($values as $k => $v) {
                    $table[] = array($k, $v);
                }

                $this->firePhp->table($group, $table);
            } else {
                // nothing to display
                $this->firePhp->log("* empty *");
            }
        }

        $this->closeCat();
    }

    /**
     * Shows user constants
     */
    protected function showUserConstants()
    {
        $constants = get_defined_constants(true);
        $this->openCat("User Constants");

        if (!empty($constants["user"])) {
            // show a key/values table
            $table      = array();
            $table[]    = array("Constant", "Value");

            foreach ($constants["user"] as $k => $v) {
                $table[] = array($k, $v);
            }

            $this->firePhp->table("Show", $table);
        } else {
            // nothing to display
            $this->firePhp->log("* empty *");
        }

        $this->closeCat();
    }

    /**
     * Shows data requests
     */
    protected function showDataRequests()
    {
        // build html of all profilers
        // each profiler has its own sub category
        $dataProfilerManager = $this->getDataProfilerManager();

        if ($dataProfilerManager && count($dataProfilerManager)) {
            $this->openCat("Data Requests");

            foreach ($dataProfilerManager as $profiler) {
                $totalTime  = count($profiler) ? $profiler->getTotalElapsedSecs() : 0;
                $totalTime  = number_format($totalTime, 4, ".", " ");
                $subTitle   = $profiler->getDataSourceName() . " (total execution time : $totalTime s)";
                $this->firePhp->group($subTitle, array("Collapsed" => true, "Color" => "magenta"));

                if (count($profiler)) {
                    foreach ($profiler as $i => $query) {
                        $this->firePhp->group("Request #$i", array("Collapsed" => false, "Color" => "green"));

                        // query text
                        $this->firePhp->log($query->getQueryText());

                        // query parameters
                        if ($query->getQueryParams()) {
                            $this->firePhp->log($query->getQueryParams(), "Params");
                        }

                        // query stats
                        $startTime          = $query->getStartMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];
                        $endTime            = $query->getEndMicrotime() ? $query->getEndMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"] : 0;

                        $startMemory        = $query->getStartMemoryUsage(true);
                        $endMemory          = $query->getEndMemoryUsage(true);
                        $startPeakMemory    = $query->getStartPeakMemoryUsage(true);
                        $endPeakMemory      = $query->getEndPeakMemoryUsage(true);

                        $this->firePhp->group("Measures", array("Collapsed" => true, "Color" => "orange"));
                        $this->firePhp->log("Started at : " . number_format($startTime, 4) . " s");
                        $this->firePhp->log("Ended at : " . number_format($endTime, 4) . " s");
                        $this->firePhp->log("Execution time : " . number_format($query->getElapsedSecs(), 4) . " s");
                        $this->firePhp->log("Start memory usage : $startMemory");
                        $this->firePhp->log("End memory usage : $endMemory");
                        $this->firePhp->log("Start memory peak usage : $startPeakMemory");
                        $this->firePhp->log("End memory peak usage : $endPeakMemory");
                        $this->firePhp->groupEnd();

                        $this->firePhp->groupEnd();
                    }
                } else {
                    // no request
                    $this->firePhp->log("No request");
                }

                $this->firePhp->groupEnd();
            }

            $this->closeCat();
        }
    }

    /**
     * Shows profiles
     */
    protected function showProfiles()
    {
        // build html of all profilers
        // each profiler has its own sub category
        $profilerManager = $this->getProfilerManager();

        if ($profilerManager && count($profilerManager)) {
            $this->openCat("Profiles");

            foreach ($profilerManager as $profiler) {
                $totalTime  = count($profiler) ? $profiler->getTotalElapsedSecs() : 0;
                $totalTime  = number_format($totalTime, 4, ".", " ");
                $subTitle   = $profiler->getProfilerName() . " (total execution time : $totalTime s)";
                $this->firePhp->group($subTitle, array("Collapsed" => true, "Color" => "magenta"));

                if (count($profiler)) {
                    foreach ($profiler as $i => $profile) {
                        $funcName   = $profile->getCallingFunction();
                        $file       = $profile->getCallingFile();
                        $line       = $profile->getCallingLine();
                        $comment    = $profile->getComment();
                        $this->firePhp->group("#$i $funcName", array("Collapsed" => false, "Color" => "green"));
                        $this->firePhp->log("Start file : $file");
                        $this->firePhp->log("Start line : $line");

                        if ($comment) {
                            $this->firePhp->log("Comment : $comment");
                        }

                        // parameters
                        if ($profile->getParams()) {
                            $this->firePhp->log($profile->getParams(), "Params");
                        }

                        // stack trace
                        $trace      = array();
                        $trace[]    = array("", "Function", "Line", "File");
                        $stackTrace = $profile->getCallingTrace();
                        $countTrace = count($stackTrace);

                        if ($stackTrace) {
                            foreach ($stackTrace as $k => $t) {
                                $trace[] = array($countTrace - 1 - $k, $t["func"], $t["line"], $t["file"]);
                            }
                        } else {
                            // empty stack trace
                            $table[] = array("* empty *");
                        }

                        $this->firePhp->table("Stack trace", $trace);

                        // query stats
                        $startTime          = $profile->getStartMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];
                        $endTime            = $profile->getEndMicrotime() ? $profile->getEndMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"] : 0;

                        $startMemory        = $profile->getStartMemoryUsage(true);
                        $endMemory          = $profile->getEndMemoryUsage(true);
                        $startPeakMemory    = $profile->getStartPeakMemoryUsage(true);
                        $endPeakMemory      = $profile->getEndPeakMemoryUsage(true);

                        $this->firePhp->group("Measures", array("Collapsed" => true, "Color" => "orange"));
                        $this->firePhp->log("Started at : " . number_format($startTime, 4) . " s");
                        $this->firePhp->log("Ended at : " . number_format($endTime, 4) . " s");
                        $this->firePhp->log("Execution time : " . number_format($profile->getElapsedSecs(), 4) . " s");
                        $this->firePhp->log("Start memory usage : $startMemory");
                        $this->firePhp->log("End memory usage : $endMemory");
                        $this->firePhp->log("Start memory peak usage : $startPeakMemory");
                        $this->firePhp->log("End memory peak usage : $endPeakMemory");
                        $this->firePhp->groupEnd();

                        $this->firePhp->groupEnd();
                    }
                } else {
                    // no profile
                    $this->firePhp->log("No profile");
                }

                $this->firePhp->groupEnd();
            }

            $this->_closeCat();
        }
    }

    /**
     * Shows application config
     */
    protected function showApplicationConfig()
    {
        if (null === $this->getApplicationConfig()) {
            return;
        }

        $this->openCat("Application Config");
        $this->firePhp->log($this->getApplicationConfig());
        $this->closeCat();
    }

    /**
     * Shows PHP config
     */
    protected function showPhpConfig()
    {
        $cfg        = ini_get_all();
        $table      = array();
        $table[]    = array("Directive", "Value");

        foreach ($cfg as $configName => $configValue) {
            $table[] = array($configName, $configValue["local_value"]);
        }

        $this->openCat("PHP Config");
        $this->firePhp->table("Show", $table);
        $this->closeCat();
    }

    /**
     * Shows loaded extensions
     */
    protected function showLoadedExtensions()
    {
        $extensions = get_loaded_extensions();
        natcasesort($extensions);
        $this->openCat("Loaded Extensions");
        $this->firePhp->log(count($extensions) . " extensions");

        foreach ($extensions as $k => $v) {
            $this->firePhp->log($v);
        }

        $this->closeCat();
    }
}
