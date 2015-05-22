<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump;

/**
 * Dumps a var to HTML
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class VarDumpHtml
{
    /**
     * Indicates if CSS styles has been outputted
     * @var boolean
     */
    private static $cssOutput = false;

    /**
     * Indicates if JS script has been outputted
     * @var boolean
     */
    private static $jsOutput = false;

    /**
     * Temp var used to hold references to object instances
     * @var array
     */
    private static $objectIdList = array();

    /**
     * Dumps a var
     * @param mixed $var var to dump
     * @param string $name [optional] var name
     * @param boolean $return [optional] if set to true, returns the result instead of displaying it.
     * @param boolean $showStackTrace [optional] whether or not show the stack trace. Defaults to true.
     * @return string|null
     */
    public static function dump($var, $name = "unknown var name", $return = false, $showStackTrace = true)
    {
        self::outputCss();
        self::outputJs();

        // render the stack trace
        if ($showStackTrace) {
            $stackTrace     = debug_backtrace(0);
            $stackTraceHtml = self::renderStackTrace($stackTrace);
        }

        // render var
        self::$objectIdList = array();
        $locale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, "C");
        $tree   = new VarTree($var);
        $tree   = $tree->getTree();
        $html   = self::renderVar($tree);
        setlocale(LC_NUMERIC, $locale);
        self::$objectIdList = array();

        // rendering
        $render     = "<pre class='debug'>";
        $render    .= "<span class='debugTitle' title='Expand/collapse all'>$name</span>";

        if ($showStackTrace) {
            $render .= "<span class='debugStackTrace'>$stackTraceHtml</span>";
        }

        $render    .= "<span class='debugContent'>$html</span>";
        $render    .= "</pre><br/>";

        if ($return) {
            return $render;
        } else {
            echo $render;
        }
    }

    /**
     * Renders the var
     * @param array $tree var tree
     * @param int $level [optional] depth level
     * @return string
     * @throws \UnexpectedValueException
     */
    protected static function renderVar($tree, $level = 0)
    {
        $render = null;

        switch ($tree["type"]) {
            // string
            case "string":
                // limits string length
                $maxLength  = 80;
                $limit      = strpos($tree["value"], "\n");
                $limit      = false !== $limit ? min($limit, $maxLength) : $maxLength;
                $string     = htmlentities($tree["value"]);

                if ($tree["length"] > $limit) {
                    $shortString    = substr($tree["value"], 0, $limit) . "...";
                    $shortString    = htmlentities($shortString);
                    $render         = "<span class='debugVarType debugToggleString' title='Expand/collapse'>string</span>(<span class='debugNumber'>{$tree["length"]}</span>) <span class='debugString'>\"$shortString\"</span><span class='debugLongString'>\"{$string}\"</span>";
                } else {
                    $render         = "<span class='debugVarType'>string</span>(<span class='debugNumber'>{$tree["length"]}</span>) <span class='debugString'>\"{$string}\"</span>";
                }

                break;

            // number
            case "integer":
            case "long":
            case "float":
            case "double":
                $render = "<span class='debugVarType'>{$tree["type"]}</span>(<span class='debugNumber'>{$tree["value"]}</span>)";
                break;

            // boolean
            case "bool":
            case "boolean":
                $render = "<span class='debugVarType'>bool</span>(<span class='debugKeyword'>{$tree["value"]}</span>)";
                break;

            // null
            case "null":
            case "NULL":
                $render = "<span class='debugKeyword'>null</span>";
                break;

            // resource
            case "resource":
                $render = "<span class='debugVarType'>resource</span>({$tree["value"]})";
                break;

            // array
            case "array":
                $pad        = str_repeat("    ", $level);
                $render    .= "<span class='debugVarType debugToggleDetails' title='Expand/collapse'>array</span>(<span class='debugNumber'>{$tree["count"]}</span>) ";
                $render    .= "<span class='debugToggleIndicator'>&rtrif;</span>";
                $render    .= "<span class='debugDetails'>";

                foreach ($tree["children"] as $k => $v) {
                    $render .= "\n$pad    [$k] = " . self::renderVar($v, $level + 1);
                }

                $render .= "</span>";
                break;

            // object
            case "object":
                // if this object instance has already been processed, we make a reference to it
                if (array_key_exists($tree["id"], self::$objectIdList)) {
                    $tree       = self::$objectIdList[$tree["id"]];
                    $render     = "<span class='debugVarType debugToReference' debug-to-reference='{$tree["id"]}' title='Go to referenced object'>object</span>(<span class='debugClassName'>{$tree["class"]}</span>)";
                    $render    .= "<span class='debugObjectId'>#{$tree["id"]}</span> (<span class='debugNumber'>{$tree["count"]}</span>) <span class='debugReferenceIndicator'>&Gt;</span>";
                    break;
                }

                // render object instance
                self::$objectIdList[$tree["id"]] = $tree;

                $pad        = str_repeat("    ", $level);
                $render     = "<span class='debugVarType debugToggleDetails' title='Expand/collapse'>object</span>(<span class='debugClassName'>{$tree["class"]}</span>)";
                $render    .= "<span class='debugObjectId' debug-reference='{$tree["id"]}'>#{$tree["id"]}</span> (<span class='debugNumber'>{$tree["count"]}</span>) ";
                $render    .= "<span class='debugToggleIndicator'>&rtrif;</span>";
                $render    .= "<span class='debugDetails'>";

                foreach ($tree["properties"] as $k => $v) {
                    $class      = $v["class"] ? "<span class='debugClassName'>{$v["class"]}:</span>" : "";
                    $key        = $v["static"] ?
                        "<span class='debugStatic'>static:</span><span class='debugAccess'>{$v["access"]}:</span>{$v["name"]}" :
                        "<span class='debugAccess'>{$v["access"]}:</span>{$class}{$v["name"]}"
                    ;
                    $render    .= "\n$pad    [$key] = " . self::renderVar($v["value"], $level + 1);
                }

                $render .= "</span>";
                break;

            // unknown type
            default:
                throw new \UnexpectedValueException("Unknown var type '{$level["type"]}'");
        }

        return $render;
    }

    /**
     * Renders the stack trace
     * @param array $stackTrace
     * @return string
     */
    protected static function renderStackTrace(array $stackTrace)
    {
        $render     = "";
        $num        = count($stackTrace) - 1;
        $numLength  = strlen($num);

        foreach ($stackTrace as $event) {
            $funcName   = array_key_exists("class", $event) ? "{$event['class']}{$event['type']}{$event['function']}" : "{$event['function']}";
            $file       = array_key_exists("file", $event)  ? $event['file'] : "-";
            $line       = array_key_exists("line", $event)  ? $event['line'] : "-";
            $args       = array_key_exists("args", $event)  ? $event['args'] : array();

            foreach ($args as $k => $arg) {
                $args[$k] = "object" == gettype($arg) ? get_class($arg) : gettype($arg);
            }

            $args       = implode(", ", $args);
            $numString  = str_pad($num, $numLength, "0", STR_PAD_LEFT);
            $funcName   = "<strong>$funcName(</strong><em>$args</em><strong>)</strong>";
            $render    .= "#$numString $funcName\n";
            $render    .= "    <strong>File :</strong> $file\n";
            $render    .= "    <strong>Line :</strong> $line\n";
            $num--;
        }

        $html   = "<span class='debugToggleStackTrace' title='Show/hide stack trace'>Stack trace</span> ";
        $html  .= "<span class='debugToggleIndicator'>&rtrif;</span>";
        $html  .= "<span class='debugStackTraceDetails'>\n\n$render</span>";
        return $html;
    }

    /**
     * Outputs CSS styles
     */
    protected static function outputCss()
    {
        if (self::$cssOutput) {
            return;
        }

        echo <<<CSS
<style type="text/css">
.debug{font-size:11px;line-height:15px;font-family:monospace;display:inline-block}
.debugTitle{cursor:pointer;background:#fce1e1;border:1px solid darkorange;border-left-width:4px;border-bottom:none;display:block;padding:5px;border-top-left-radius:2px;border-top-right-radius:2px;color:red;font-weight:bold}
.debugStackTrace{background:#dae8ed;border:1px solid #97b5bf;border-left-width:4px;border-bottom:none;display:block;padding:5px}
.debugContent{background:white;border:1px solid #999;border-left-width:4px;display:block;padding:8px;border-bottom-left-radius:2px;border-bottom-right-radius:2px}
.debugVarType{color:#0000c4}
.debugKeyword{color:#007200}
.debugString,.debugLongString{color:#c40000}
.debugLongString{display:none}
.debugNumber{color:red}
.debugClassName{color:#ac00ac}
.debugAccess{color:darkorange;font-style:italic}
.debugStatic{color:darkred;font-style:italic}
.debugToggleDetails,.debugToReference,.debugToggleString,.debugToggleStackTrace{border-bottom:1px dotted black;cursor:pointer}
.debugToggleIndicator,.debugReferenceIndicator{font-size:12px}
.debugDetails{display:none}
.debugReferenced{background:#ff7070;color:black !important;border-radius:2px}
.debugReference{background:#ffa97a;color:black !important;border-radius:2px}
.debugObjectId{padding:0 3px;color:silver;transition:background 0.4s ease,color 0.4s ease}
.debugStackTraceDetails{display:none}
.debugStackTraceDetails em{color:grey}
</style>
CSS;
        self::$cssOutput = true;
    }

    /**
     * Outputs JS script
     */
    protected static function outputJs()
    {
        if (self::$jsOutput) {
            return;
        }

        echo <<<JS
<script type="text/javascript">
if ("undefined" == typeof jQuery) {
    var fileref = document.createElement("script");
    fileref.setAttribute("type","text/javascript");
    fileref.setAttribute("src", "http://code.jquery.com/jquery-1.11.1.min.js");
    fileref.onload = debugInit;
    document.getElementsByTagName("head")[0].appendChild(fileref);
} else {
    jQuery(debugInit);
}

function debugInit() {
    jQuery(".debugTitle").click(function(event) {
        var element = jQuery(event.currentTarget);
        var action  = element.hasClass("debugToggleAllShown") ? "hide" : "show";
        element.closest(".debug").find(".debugDetails").each(function(i, e) {
            if ("show" == action) {
                debugShowDetails(e);
            } else {
                debugHideDetails(e);
            }
        });

        if ("show" == action) {
            element.addClass("debugToggleAllShown");
        } else {
            element.removeClass("debugToggleAllShown");
        }
    });

    jQuery(".debugToggleDetails").click(function(event) {
        var element = jQuery(event.currentTarget);
        var details = element.nextAll(".debugDetails:eq(0)");

        if (details.is(":visible")) {
            debugHideDetails(details);
        } else {
            debugShowDetails(details);
        }
    });

    jQuery(".debugToggleStackTrace").click(function(event) {
        var element = jQuery(event.currentTarget);
        var details = element.nextAll(".debugStackTraceDetails:eq(0)");

        if (details.is(":visible")) {
            debugHideDetails(details);
        } else {
            debugShowDetails(details);
        }
    });

    jQuery(".debugToggleString").click(function(event) {
        var element             = jQuery(event.currentTarget);
        var elementShortString  = element.nextAll(".debugString:eq(0)");
        var elementLongString   = element.nextAll(".debugLongString:eq(0)");

        if (elementShortString.is(":visible")) {
            elementShortString.hide();
            elementLongString.show();
        } else {
            elementShortString.show();
            elementLongString.hide();
        }
    });

    jQuery(".debugToReference").click(function(event) {
        var element     = jQuery(event.currentTarget);
        var reference   = element.attr("debug-to-reference");
        var elementRef  = element.closest(".debug").find("[debug-reference='" + reference + "']");

        // show each ancestor element of the referenced element
        var refAncestors = elementRef.parents(".debugDetails");

        refAncestors.each(function(i, e) {
            e = jQuery(e);

            if (!e.is(":visible")) {
                debugShowDetails(e);
            }
        });

        // highlights reference element and referenced element
        element.closest(".debug").find(".debugReferenced").removeClass("debugReferenced");
        elementRef.addClass("debugReferenced");

        element.closest(".debug").find(".debugReference").removeClass("debugReference");
        element.nextAll(".debugObjectId:eq(0)").addClass("debugReference");

        // scroll to referenced element
        jQuery("html, body").animate({
            scrollTop: elementRef.position().top
        }, "fast");
    });
}

function debugShowDetails(details) {
    details     = jQuery(details);
    var icon    = details.prevAll(".debugToggleIndicator:eq(0)");
    details.show();
    icon.html("&dtrif;");
}

function debugHideDetails(details) {
    details     = jQuery(details);
    var icon    = details.prevAll(".debugToggleIndicator:eq(0)");
    details.hide();
    icon.html("&rtrif;");
}
</script>
JS;
        self::$jsOutput = true;
    }
}
