<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Exception\Renderer;

use Bugzorcist\VarDump\VarDumpHtml;
use Bugzorcist\Utils\UnitFormatter;

/**
 * HTML exception renderer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ExceptionRendererHtml extends ExceptionRendererAbstract
{
    /**
     * Indicates if JS script has been outputted
     * @var boolean
     */
    private static $jsOutput = false;

    /**
     * Indicates if CSS styles has been outputted
     * @var boolean
     */
    private static $cssOutput = false;

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $exception  = $this->getException();
        $first      = true;
        $content    = "";
        $host       = "";
        $uri        = "";

        if (array_key_exists("HTTP_HOST", $_SERVER)) {
            $host   = htmlentities($_SERVER['HTTP_HOST']);
            $host   = "<div class='exceptionRenderDescriptionElement'><strong>Host</strong> <code>{$_SERVER['HTTP_HOST']}</code></div>";
        }

        if (array_key_exists("REQUEST_METHOD", $_SERVER) && array_key_exists("REQUEST_URI", $_SERVER)) {
            $requesturi = htmlentities($_SERVER['REQUEST_URI']);
            $uri        = "<div class='exceptionRenderDescriptionElement'><strong>URI</strong> <code>{$_SERVER['REQUEST_METHOD']} $requesturi</code></div>";
        }

        while ($exception instanceof \Exception) {
            $exceptionType  = get_class($exception);
            $message        = $exception->getMessage() ? $exception->getMessage() : "no message";
            $file           = $exception->getFile();
            $line           = $exception->getLine();
            $code           = $exception->getCode();
            $bodyAdd        = "";

            $message        = htmlentities($message);
            $message        = nl2br($message);

            // only the first exception has all details
            if ($first) {
                $bodyAdd = "
                    " . $this->getRequestHtml() . "
                    " . $this->getResponseHtml() . "
                    " . $this->getConstantsHtml() . "
                    " . $this->getUserConstantsHtml() . "
                    " . $this->getDataRequestsHtml() . "
                    " . $this->getProfilesHtml() . "
                    " . $this->getApplicationConfigHtml() . "
                    " . $this->getPhpConfigHtml() . "
                    " . $this->getIncludedFilesHtml() . "
                    " . $this->getLoadedExtensionsHtml() . "
                    " . $this->getMemoryUsageHtml() . "
                    " . $this->getExecutionTimeHtml() . "
                ";
            }

            // build exception html
            $content .= "
                <div class='exceptionRender'>
                    <div class='exceptionRenderTitle'>
                        <code>$exceptionType</code>
                    </div>
                    <div class='exceptionRenderBody'>
                        <div class='exceptionRenderDescription'>
                            <div class='exceptionRenderDescriptionTitle'>$message</div>
                            <div class='exceptionRenderDescriptionElement'><strong>File</strong> <code>$file</code></div>
                            <div class='exceptionRenderDescriptionElement'><strong>Line</strong> <code>$line</code></div>
                            <div class='exceptionRenderDescriptionElement'><strong>Code</strong> <code>$code</code></div>
                            $host
                            $uri
                        </div>

                        " . $this->getStackTraceHtml($exception) . "
                        $bodyAdd
                    </div>
                </div>
            ";

            // render previous exceptions if any
            $exception  = $exception->getPrevious();
            $first      = false;
        }

        $this->outputCss();
        $this->outputJs();
        echo $content;
    }

    /**
     * Dumps a var
     * @param mixed $var var to dump
     * @param string $legend [optional] legend
     * @return string
     */
    protected function varDump($var, $legend = NULL)
    {
        return VarDumpHtml::dump($var, $legend, true, false);
    }

    /**
     * Creates a category
     * @param string $title category title
     * @param string $content category content
     * @return string
     */
    protected function cat($title, $content)
    {
        return "
            <div class='exceptionRenderElement'>
                <div class='exceptionRenderElementTitle'>$title <span>&rtrif;</span></div>
                <div class='exceptionRenderElementContent'>
                    $content
                </div>
            </div>
        ";
    }

    /**
     * Returns the stack trace HTML code
     * @param \Exception $exception
     * @return string
     */
    protected function getStackTraceHtml(\Exception $exception)
    {
        $trace      = $exception->getTrace();
        $count      = count($trace);
        $digits     = strlen(max($count - 1, 0));
        $stackTrace = "";

        if (empty($trace)) {
            $trace      = array();
            $trace[]    = array(
                "main" => true
            );
        }

        // build stack trace html
        foreach ($trace as $k => $v) {
            $num            = str_pad($count - 1 - $k, $digits, "0", STR_PAD_LEFT);

            if (array_key_exists("main", $v)) {
                $funcName = "{main}";
            } else {
                $funcName = array_key_exists("class", $v) ? "{$v['class']}{$v['type']}{$v['function']}()" : "{$v['function']}()";
            }

            $file           = array_key_exists("file", $v) ? $v['file'] : "-";
            $line           = array_key_exists("line", $v) ? $v['line'] : "-";
            $args           = array_key_exists("args", $v) ? $v['args'] : array();
            $dump           = $this->varDump($args, "$funcName args");

            $stackTrace    .= "
                <div class='exceptionRenderElementStackTrace'>
                    <div class='exceptionRenderElementStackTraceHeader'>
                        <div class='exceptionRenderElementStackTraceTitle'>#$num $funcName</div>
                        <div class='exceptionRenderElementStackTraceDesc'><strong>File</strong> <code>$file</code></div>
                        <div class='exceptionRenderElementStackTraceDesc'><strong>Line</strong> <code>$line</code></div>
                    </div>
                    <div class='exceptionRenderElementStackTraceArgs'>$dump</div>
                </div>
            ";
        }

        return $this->cat("StackTrace", $stackTrace);
    }

    /**
     * Returns the included files HTML code
     * @return string
     */
    protected function getIncludedFilesHtml()
    {
        $files    = get_included_files();
        $content  = "";

        foreach ($files as $k => $v) {
            $n          = $k + 1;
            $content   .= "<tr><td class='exceptionRenderAlignRight'>$n</td><td><code>$v</code></td></tr>";
        }

        $count      = count($files);
        $content    = "<p>$count files</p><table>$content</table>";
        return $this->cat("Included Files", $content);
    }

    /**
     * Returns the HTTP request HTML code
     * @return string
     */
    protected function getRequestHtml()
    {
        $parsedHttpRequest  = "";
        $data               = array(
            '$_GET'     => $_GET,
            '$_POST'    => $_POST,
            '$_COOKIE'  => $_COOKIE,
            '$_SERVER'  => $_SERVER,
            '$_ENV'     => $_ENV
        );

        foreach ($data as $k => $v) {
            // build table lines
            $table = "";

            foreach ($v as $name => $value) {
                if (!is_scalar($value)) {
                    $value = $this->varDump($value, $name);
                }

                $table .= "<tr><td><code>$name</code></td><td><code>$value</code></td></tr>";
            }

            if (empty($table)) {
                $table = "<tr><td colspan='2' class='exceptionRenderAlignCenter'>Empty</td></tr>";
            }

            // build table
            $table              = "<table><tr><th>Name</th><th>Value</th></tr>$table</table>";
            $parsedHttpRequest .= "
                <div class='exceptionRenderElementRequest'>
                    <div class='exceptionRenderElementRequestTitle'>$k <span>&rtrif;</span></div>
                    <div class='exceptionRenderElementRequestValues'>$table</div>
                </div>
            ";
        }

        return $this->cat("Request", $parsedHttpRequest);
    }

    /**
     * Returns the HTTP response HTML code
     * @return string
     */
    protected function getResponseHtml()
    {
        $headers      = headers_list();
        $httpResponse = "<tr><th>Headers</th></tr>";

        foreach ($headers as $k => $v) {
            $httpResponse .= "<tr><td><code>$v</code></td></tr>";
        }

        $httpResponse = "<table>$httpResponse</table>";

        return $this->cat("Response", $httpResponse);
    }

    /**
     * Returns the memory usage HTML code
     */
    protected function getMemoryUsageHtml()
    {
        $memory       = UnitFormatter::formatByte($this->getMemoryUsage());
        $memoryPeak   = UnitFormatter::formatByte($this->getMemoryPeakUsage());
        $memoryLimit  = ini_get("memory_limit");

        if (-1 == $memoryLimit) {
            $memoryLimit = "-1 (no limit)";
        }

        $content      = "
            <div class='exceptionRenderMemoryUsage'>
                <strong>Memory used</strong>
                $memory
            </div>
            <div class='exceptionRenderMemoryUsage'>
                <strong>Memory peak</strong>
                $memoryPeak
            </div>
            <div class='exceptionRenderMemoryUsage'>
                <strong>Limit</strong>
                $memoryLimit
            </div>
        ";

        return $this->cat("Memory Usage", $content);
    }

    /**
     * Returns the execution time HTML code
     * @return string
     */
    protected function getExecutionTimeHtml()
    {
        $time       = $this->getMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];
        $time       = number_format($time, "7", ",", " ");
        $content    = "$time s";

        $content = "<code class='exceptionRenderExecutionTime'>$content</code>";
        return $this->cat("Execution Time", $content);
    }

    /**
     * Returns the constants HTML code
     * @return string
     */
    protected function getConstantsHtml()
    {
        $content    = "";
        $constants  = get_defined_constants(true);
        unset($constants['user']);

        foreach ($constants as $k => $v) {
            // build table lines
            $table = "";

            foreach ($v as $name => $value) {
                $table .= "<tr><td><code>$name</code></td><td><code>$value</code></td></tr>";
            }

            if (empty($table)) {
                $table = "<tr><td colspan='2' class='exceptionRenderAlignCenter'>No constants defined</td></tr>";
            }

            // build table
            $table      = "<table><tr><th>Name</th><th>Value</th></tr>$table</table>";
            $content   .= "
                <div class='exceptionRenderElementConstants'>
                    <div class='exceptionRenderElementConstantsTitle'>$k <span>&rtrif;</span></div>
                    <div class='exceptionRenderElementConstantsValues'>$table</div>
                </div>
            ";
        }

        return $this->cat("Constants", $content);
    }

    /**
     * Returns the user constants HTML code
     * @return string
     */
    protected function getUserConstantsHtml()
    {
        $constants  = get_defined_constants(true);
        $constants  = array_key_exists("user", $constants) ? $constants['user'] : array();
        $content    = "<tr><th>Name</th><th>Value</th></tr>";

        foreach ($constants as $k => $v) {
            $content .= "<tr><td><code>$k</code></td><td><code>$v</code></td></tr>";
        }

        $content = "<table>$content</table>";
        return $this->cat("User Constants", $content);
    }

    /**
     * Returns the data requests HTML code
     * @return string|null
     */
    protected function getDataRequestsHtml()
    {
        // build html of all profilers
        // each profiler has its own sub category
        $content                = "";
        $dataProfilerManager    = $this->getDataProfilerManager();

        if ($dataProfilerManager && count($dataProfilerManager)) {
            foreach ($dataProfilerManager as $profiler) {
                $subContent = "";
                $totalTime  = 0;
                $count      = count($profiler);
                $digits     = strlen(max($count - 1, 0));

                if ($count) {
                    $totalTime = $profiler->getTotalElapsedSecs();

                    foreach ($profiler as $i => $profile) {
                        $startTime          = $profile->getStartMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];
                        $endTime            = $profile->getEndMicrotime() ? $profile->getEndMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"] : 0;
                        $queryParams        = $profile->getQueryParams();

                        $startMemory        = $profile->getStartMemoryUsage(true);
                        $endMemory          = $profile->getEndMemoryUsage(true);
                        $startPeakMemory    = $profile->getStartPeakMemoryUsage(true);
                        $endPeakMemory      = $profile->getEndPeakMemoryUsage(true);

                        // query text
                        $query              = $profiler->getFormatter()->formatHtml($profile->getQueryText());

                        // query parameters
                        $params             = "";

                        if ($queryParams) {
                            $maxKeyLength = 0;

                            // look for the longest parameter name
                            foreach ($queryParams as $paramName => $paramValue) {
                                $maxKeyLength = strlen($paramName) > $maxKeyLength ? strlen($paramName) : $maxKeyLength;
                            }

                            // build parameters html
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
                                    default:
                                        $paramValue = "";
                                }

                                $paramName  = str_replace(" ", "&nbsp;", str_pad($paramName, $maxKeyLength, " ", STR_PAD_RIGHT));
                                $params    .= "<code><span>$paramName =&gt;</span> <span>($paramType)</span> $paramValue</code>";
                            }
                        }

                        // query stats
                        $stats = "
                            <table>
                                <tr>
                                    <th colspan='2'>Execution time</th>
                                    <th colspan='2'>Memory usage</th>
                                    <th colspan='2'>Memory peak usage</th>
                                </tr>
                                <tr>
                                    <td>Started at</td><td>" . number_format($startTime, 4, ".", " ") . " s</td>
                                    <td>Start</td><td>$startMemory</td>
                                    <td>Start</td><td>$startPeakMemory</td>
                                </tr>
                                <tr>
                                    <td>Ended at</td><td>" . number_format($endTime, 4, ".", " ") . " s</td>
                                    <td>End</td><td>$endMemory</td>
                                    <td>End</td><td>$endPeakMemory</td>
                                </tr>
                                <tr>
                                    <td>Execution time</td><td>" . number_format($profile->getElapsedSecs(), 4, ".", " ") . " s</td>
                                    <td>&nbsp;</td><td>&nbsp;</td>
                                    <td>&nbsp;</td><td>&nbsp;</td>
                                </tr>
                            </table>
                        ";

                        // final profile html
                        $num            = str_pad($i, $digits, "0", STR_PAD_LEFT);
                        $subContent    .= "
                            <div class='exceptionRenderElementDataProfilesProfile'>
                                <div class='exceptionRenderElementDataProfilesProfileTop'>#$num</div>
                                <div class='exceptionRenderElementDataProfilesProfileTop'>
                                    <div class='exceptionRenderElementDataProfilesProfileQuery'><code>$query</code></div>
                                    <div class='exceptionRenderElementDataProfilesProfileParams'>$params</div>
                                    <div class='exceptionRenderElementDataProfilesProfileStats'>$stats</div>
                                </div>
                            </div>
                        ";
                    }
                } else {
                    // no request
                    $subContent = "<div class='exceptionRenderElementDataProfilesProfile'>No request</div>";
                }

                // create sub category for this profiler
                $numProfiles    = count($profiler);
                $s              = $numProfiles > 1 ? "s" : "";
                $totalTime      = number_format($totalTime, 4, ".", " ");
                $subTitle       = $profiler->getDataSourceName() . " <em>($numProfiles profile$s | total execution time : $totalTime s)</em>";
                $content       .= "
                    <div class='exceptionRenderElementDataProfiles'>
                        <div class='exceptionRenderElementDataProfilesTitle'>$subTitle <span>&rtrif;</span></div>
                        <div class='exceptionRenderElementDataProfilesValues'>$subContent</div>
                    </div>
                ";
            }
        } elseif ($dataProfilerManager) {
            // no profiler
            $content = "No data profiler";
        } else {
            // no profiler manager
            return;
        }

        return $this->cat("Data Requests", $content);
    }

    /**
     * Returns the profiles HTML code
     * @return string|null
     */
    protected function getProfilesHtml()
    {
        // build html of all profilers
        // each profiler has its own sub category
        $content            = "";
        $profilerManager    = $this->getProfilerManager();

        if ($profilerManager && count($profilerManager)) {
            foreach ($profilerManager as $profiler) {
                $subContent = "";
                $totalTime  = 0;
                $count      = count($profiler);
                $digits     = strlen(max($count - 1, 0));

                if ($count) {
                    $totalTime = $profiler->getTotalElapsedSecs();

                    foreach ($profiler as $numProfile => $profile) {
                        // args
                        $args       = $this->varDump($profile->getParams(), "args");

                        // profile stats
                        $startTime  = $profile->getStartMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"];
                        $endTime    = $profile->getEndMicrotime() ? $profile->getEndMicrotime() - $_SERVER["REQUEST_TIME_FLOAT"] : 0;

                        $stats      = "
                            <table>
                                <tr>
                                    <th colspan='2'>Execution time</th>
                                    <th colspan='2'>Memory usage</th>
                                    <th colspan='2'>Memory peak usage</th>
                                </tr>
                                    <td>Started at</td><td>" . number_format($startTime, 4, ".", " ") . " s</td>
                                    <td>Start</td><td>" . $profile->getStartMemoryUsage(true) . "</td>
                                    <td>Start</td><td>" . $profile->getStartPeakMemoryUsage(true) . "</td>
                                </tr>
                                    <td>Ended at</td><td>" . number_format($endTime, 4, ".", " ") . " s</td>
                                    <td>End</td><td>" . $profile->getEndMemoryUsage(true) . "</td>
                                    <td>End</td><td>" . $profile->getEndPeakMemoryUsage(true) . "</td>
                                </tr>
                                <tr>
                                    <td>Execution time</td><td>" . number_format($profile->getElapsedSecs(), 4, ".", " ") . " s</td>
                                    <td>&nbsp;</td><td>&nbsp;</td>
                                    <td>&nbsp;</td><td>&nbsp;</td>
                                </tr>
                            </table>
                        ";

                        // stack trace
                        $trace      = "";
                        $stackTrace = $profile->getCallingTrace();
                        $countTrace = count($stackTrace);

                        if ($stackTrace) {
                            foreach ($stackTrace as $k => $t) {
                                $num    = $countTrace - 1 - $k;
                                $trace .= "
                                    <tr>
                                        <td>$num</td>
                                        <td><code>{$t["func"]}</code></td>
                                        <td><code>{$t["line"]}</code></td>
                                        <td><code>{$t["file"]}</code></td>
                                    </tr>
                                ";
                            }
                        } else {
                            // empty stack trace
                            $trace .= "
                                <tr>
                                    <td colspan='4'>Empty</td>
                                </tr>
                            ";
                        }

                        $trace = "
                            <table class='exceptionArray'>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Function</th>
                                    <th>Line</th>
                                    <th>File</th>
                                </tr>
                                $trace
                            </table>
                        ";

                        // final profile html
                        $funcName       = $profile->getCallingFunction();
                        $file           = $profile->getCallingFile();
                        $line           = $profile->getCallingLine();
                        $comment        = $profile->getComment() ?
                            "<div class='exceptionRenderElementProfilesProfileDesc'><strong>Comment</strong> " . $profile->getComment() . "</div>" :
                            ""
                        ;
                        $num            = str_pad($numProfile, $digits, "0", STR_PAD_LEFT);
                        $subContent    .= "
                            <div class='exceptionRenderElementProfilesProfile'>
                                <div class='exceptionRenderElementProfilesProfileHeader'>
                                    <div class='exceptionRenderElementProfilesProfileTitle'>#$num $funcName</div>
                                    <div class='exceptionRenderElementProfilesProfileDesc'><strong>Start file</strong> <code>$file</code></div>
                                    <div class='exceptionRenderElementProfilesProfileDesc'><strong>Start line</strong> <code>$line</code></div>
                                    $comment
                                </div>
                                <div class='exceptionRenderElementProfilesProfileDetails'>
                                    <div class='exceptionRenderElementProfilesProfileArgs'>$args</div>
                                    <div class='exceptionRenderElementProfilesProfileStats'>$stats</div>
                                    <div class='exceptionRenderElementProfilesProfileTrace'>$trace</div>
                                </div>
                            </div>
                        ";
                    }
                } else {
                    // no profile
                    $subContent = "<div class='exceptionRenderElementProfilesProfile'>No profile</div>";
                }

                // create sub category for this profiler
                $numProfiles    = count($profiler);
                $s              = $numProfiles > 1 ? "s" : "";
                $totalTime      = number_format($totalTime, 4, ".", " ");
                $subTitle       = $profiler->getProfilerName() . " <em>($numProfiles profile$s | total execution time : $totalTime s)</em>";
                $content       .= "
                    <div class='exceptionRenderElementProfiles'>
                        <div class='exceptionRenderElementProfilesTitle'>$subTitle <span>&rtrif;</span></div>
                        <div class='exceptionRenderElementProfilesValues'>$subContent</div>
                    </div>
                ";
            }
        } elseif ($profilerManager) {
            // no profiler
            $content = "No profiler";
        } else {
            // no profiler manager
            return;
        }

        return $this->cat("Profiles", $content);
    }

    /**
     * Returns the application config HTML code
     * @return string|null
     */
    protected function getApplicationConfigHtml()
    {
        if (null === $this->getApplicationConfig()) {
            return;
        }

        return $this->cat("Application Config", $this->varDump($this->getApplicationConfig(), "Application config"));
    }

    /**
     * Returns the PHP config HTML code
     * @return string
     */
    protected function getPhpConfigHtml()
    {
        $cfg        = ini_get_all();
        $content    = "<tr><th>Directive</th><th>Current value</th><th>Global value</th></tr>";

        foreach ($cfg as $configName => $configValue) {
            $configValue["local_value"]     = str_replace(array(",", ":", ";"), array(",<br/>", ":<br/>", ";<br/>"), $configValue["local_value"]);
            $configValue["global_value"]    = str_replace(array(",", ":", ";"), array(",<br/>", ":<br/>", ";<br/>"), $configValue["global_value"]);
            $content                       .= "<tr><td><code>$configName</code></td><td><code>{$configValue["local_value"]}</code></td><td><code>{$configValue["global_value"]}</code></td></tr>";
        }

        $content = "<table>$content</table>";
        return $this->cat("PHP Config", $content);
    }

    /**
     * Returns the loaded extensions HTML code
     * @return string
     */
    protected function getLoadedExtensionsHtml()
    {
        $extensions = get_loaded_extensions();
        natcasesort($extensions);
        $extensions = array_values($extensions);
        $content    = "";

        foreach ($extensions as $k => $v) {
            $n          = $k + 1;
            $content   .= "<tr><td class='exceptionRenderAlignRight'>$n</td><td><code>$v</code></td></tr>";
        }

        $count      = count($extensions);
        $content    = "<p>$count extensions</p><table>$content</table>";
        $content    = "<table>$content</table>";
        return $this->cat("Loaded Extensions", $content);
    }

    /**
     * Outputs CSS styles
     */
    protected function outputCss()
    {
        if (self::$cssOutput) {
            return;
        }

        self::$cssOutput = true;
        echo <<<CSS
<style type="text/css">
.exceptionRender{font-family:sans-serif;font-size:13px;margin-bottom:15px}
.exceptionRenderTitle{cursor:pointer;background:#fce1e1;border:1px solid darkorange;border-left-width:4px;border-bottom:none;display:block;padding:5px;border-top-left-radius:2px;border-top-right-radius:2px;color:red;font-weight:bold}
.exceptionRenderBody{background: #d9dff6;border: 1px solid #536ddc;border-left-width:4px;padding:5px;border-bottom-left-radius:2px;border-bottom-right-radius:2px}
.exceptionRenderDescription{margin-bottom:15px}
.exceptionRenderDescriptionTitle{margin-bottom:15px;font-size:16px;font-weight:bold}
.exceptionRenderDescriptionElement strong{display:inline-block;width:50px;font-weight:bold;color:green}

.exceptionRenderElementTitle{padding:5px;cursor:pointer;font-weight:bold;color:#3d3d3d;background:#c1d8e0;border: 1px solid #7ba1ad;border-left-width:4px;border-top:none;transition:background 0.3s ease}
.exceptionRenderElementTitle:hover{background:#9bcdde;border-color:#2d8fad}
.exceptionRenderElement:last-child .exceptionRenderElementTitle{border-bottom-left-radius:2px;border-bottom-right-radius:2px}
.exceptionRenderElementContent{display:none;margin-left:20px;margin-right:5px;padding:5px;background:#ddddee;border:1px solid #a0a0bb;border-top:none;border-bottom:none;border-left-width:3px}

.exceptionRenderAlignCenter{text-align:center}
.exceptionRenderAlignRight{text-align:right}
.exceptionRenderAlignLeft{text-align:left}
.exceptionRenderElementContent table{border-collapse:collapse}
.exceptionRenderElementContent table th,.exceptionRenderElementContent table td{padding:3px}
.exceptionRenderElementContent table th{color:#7d7d7d;font-size:12px}
.exceptionRenderElementContent table td{border:1px solid #8d8d8d;background:#f0edf0;color:#5d5d5d;transition:background 0.3s ease}
.exceptionRenderElementContent table tr:nth-child(even) td{background:#f0e5f0}
.exceptionRenderElementContent table tr:hover td{background:#fad898}

.exceptionRenderElementStackTraceHeader{cursor:pointer;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementStackTraceHeader:hover{background:#c5c5eb}
.exceptionRenderElementStackTraceArgs{display:none;padding-left:40px}
.exceptionRenderElementStackTraceTitle{color:#5d5d5d;font-weight:bold}
.exceptionRenderElementStackTraceDesc{color:#5d5d5d}
.exceptionRenderElementStackTraceDesc strong{display:inline-block;margin-left:30px;width:50px;font-weight:bold}

.exceptionRenderElementRequestTitle{cursor:pointer;color:#5d5d5d;font-weight:bold;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementRequestTitle:hover{background:#c5c5eb}
.exceptionRenderElementRequestValues{display:none;margin-left:40px;margin-bottom:15px}
.exceptionRenderElementRequestValues table td:nth-child(1){border-left-width:3px}

.exceptionRenderElementConstantsTitle{cursor:pointer;color:#5d5d5d;font-weight:bold;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementConstantsTitle:hover{background:#c5c5eb}
.exceptionRenderElementConstantsValues{display:none;margin-left:40px;margin-bottom:15px}
.exceptionRenderElementConstantsValues table td:nth-child(1){border-left-width:3px}

.exceptionRenderExecutionTime{display:block;padding:5px}

.exceptionRenderMemoryUsage{padding:5px}
.exceptionRenderMemoryUsage strong{display:inline-block;width:120px}

.exceptionRenderElementProfilesTitle{cursor:pointer;color:#5d5d5d;font-weight:bold;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementProfilesTitle:hover{background:#c5c5eb}
.exceptionRenderElementProfilesTitle em{color:#9d9d9d}
.exceptionRenderElementProfilesValues{display:none}
.exceptionRenderElementProfilesProfile{margin-left:20px;margin-bottom:15px}
.exceptionRenderElementProfilesProfileHeader{cursor:pointer;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementProfilesProfileHeader:hover{background:#c5c5eb}
.exceptionRenderElementProfilesProfileTitle{color:#5d5d5d;font-weight:bold}
.exceptionRenderElementProfilesProfileDesc{color:#5d5d5d}
.exceptionRenderElementProfilesProfileDesc strong{display:inline-block;margin-left:30px;width:80px;font-weight:bold}
.exceptionRenderElementProfilesProfileDetails{display:none;padding-left:60px}
.exceptionRenderElementProfilesProfileTrace,.exceptionRenderElementProfilesProfileStats{margin-bottom:15px}
.exceptionRenderElementProfilesProfileStats table{font-size:12px}
.exceptionRenderElementProfilesProfileStats th,.exceptionRenderElementProfilesProfileStats td{padding-left:10px !important;padding-right:10px !important}
.exceptionRenderElementProfilesProfileStats td{background:none !important;border-width:0 !important}
.exceptionRenderElementProfilesProfileStats td:hover{background:none !important}
.exceptionRenderElementProfilesProfileStats td:nth-child(even){text-align:right}
.exceptionRenderElementProfilesProfileStats td:nth-child(odd){border-left-width:3px !important}
.exceptionRenderElementProfilesProfileTrace table td:nth-child(1){border-left-width:3px;text-align:right}

.exceptionRenderElementDataProfilesTitle{cursor:pointer;color:#5d5d5d;font-weight:bold;padding:5px;border-radius:2px;transition:background 0.3s ease}
.exceptionRenderElementDataProfilesTitle:hover{background:#c5c5eb}
.exceptionRenderElementDataProfilesTitle em{color:#9d9d9d}
.exceptionRenderElementDataProfilesValues{display:none}
.exceptionRenderElementDataProfilesProfile{margin-left:40px;margin-bottom:10px;padding:5px;background:#ccccee;border-radius:3px;white-space:nowrap}
.exceptionRenderElementDataProfilesProfile:nth-child(even){background:#ccddcc}
.exceptionRenderElementDataProfilesProfileTop{display:inline-block;vertical-align:top;white-space:normal}
.exceptionRenderElementDataProfilesProfileTop:nth-child(1){margin-right:15px;font-weight:bold}
.exceptionRenderElementDataProfilesProfileQuery,.exceptionRenderElementDataProfilesProfileParams,.exceptionRenderElementDataProfilesProfileStats{margin-bottom:15px}
.exceptionRenderElementDataProfilesProfileParams code{display:block}
.exceptionRenderElementDataProfilesProfileParams span:nth-child(1){color:magenta}
.exceptionRenderElementDataProfilesProfileParams span:nth-child(2){color:darkorange}
.exceptionRenderElementDataProfilesProfileStats table{font-size:12px}
.exceptionRenderElementDataProfilesProfileStats th,.exceptionRenderElementDataProfilesProfileStats td{padding-left:10px !important;padding-right:10px !important}
.exceptionRenderElementDataProfilesProfileStats td{background:none !important;border-width:0 !important}
.exceptionRenderElementDataProfilesProfileStats td:hover{background:none !important}
.exceptionRenderElementDataProfilesProfileStats td:nth-child(even){text-align:right}
.exceptionRenderElementDataProfilesProfileStats td:nth-child(odd){border-left-width:3px !important}
.exceptionRenderElementDataProfilesProfileTrace table td:nth-child(1){border-left-width:3px;text-align:right}
</style>
CSS;
    }

    /**
     * Outputs JS scripts
     */
    protected function outputJs()
    {
        if (self::$jsOutput) {
            return;
        }

        self::$jsOutput = true;
        echo <<<JAVASCRIPT
<script type="text/javascript">
if ("undefined" == typeof jQuery) {
    var fileref = document.createElement("script");
    fileref.setAttribute("type","text/javascript");
    fileref.setAttribute("src", "http://code.jquery.com/jquery-1.11.1.min.js");
    fileref.onload = exceptionRenderInit;
    document.getElementsByTagName("head")[0].appendChild(fileref);
} else {
    jQuery(exceptionRenderInit);
}

function exceptionRenderInit() {
    // FIX: remove background-color CSS property from SQL formatted blocks
    jQuery("pre").css("background-color", null);

    // exception details
    jQuery(".exceptionRenderTitle").click(function(event) {
        var element         = jQuery(event.currentTarget);
        var renderElements  = element.parent().find(".exceptionRenderBody .exceptionRenderElement");

        if (renderElements.is(":visible")) {
            renderElements.hide();
        } else {
            renderElements.show();
        }
    });

    // elements
    jQuery(".exceptionRenderElementTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var content = element.parent().find(".exceptionRenderElementContent");
        var icon    = element.find("span");

        if (content.is(":visible")) {
            content.hide();
            icon.html("&rtrif;");
        } else {
            content.show();
            icon.html("&dtrif;");
        }
    });

    // stack trace
    jQuery(".exceptionRenderElementStackTraceHeader").click(function(event) {
        var element = jQuery(event.currentTarget);
        var args    = element.parent().find(".exceptionRenderElementStackTraceArgs");

        if (args.is(":visible")) {
            args.hide();
        } else {
            args.show();
        }
    });

    // request
    jQuery(".exceptionRenderElementRequestTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var values  = element.parent().find(".exceptionRenderElementRequestValues");
        var icon    = element.find("span");

        if (values.is(":visible")) {
            values.hide();
            icon.html("&rtrif;");
        } else {
            values.show();
            icon.html("&dtrif;");
        }
    });

    // constants
    jQuery(".exceptionRenderElementConstantsTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var values  = element.parent().find(".exceptionRenderElementConstantsValues");
        var icon    = element.find("span");

        if (values.is(":visible")) {
            values.hide();
            icon.html("&rtrif;");
        } else {
            values.show();
            icon.html("&dtrif;");
        }
    });

    // profiles
    jQuery(".exceptionRenderElementProfilesTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var values  = element.parent().find(".exceptionRenderElementProfilesValues");
        var icon    = element.find("span");

        if (values.is(":visible")) {
            values.hide();
            icon.html("&rtrif;");
        } else {
            values.show();
            icon.html("&dtrif;");
        }
    });

    jQuery(".exceptionRenderElementProfilesProfileHeader").click(function(event) {
        var element = jQuery(event.currentTarget);
        var args    = element.parent().find(".exceptionRenderElementProfilesProfileDetails");

        if (args.is(":visible")) {
            args.hide();
        } else {
            args.show();
        }
    });

    // data profiles
    jQuery(".exceptionRenderElementDataProfilesTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var values  = element.parent().find(".exceptionRenderElementDataProfilesValues");
        var icon    = element.find("span");

        if (values.is(":visible")) {
            values.hide();
            icon.html("&rtrif;");
        } else {
            values.show();
            icon.html("&dtrif;");
        }
    });
}
</script>
JAVASCRIPT;
    }
}
