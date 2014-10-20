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
 * Dumps a var to FirePHP
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class VarDumpFirePhp
{
    /**
     * Dumps a var
     * @param mixed $var var to dump
     * @param string $name [optional] var name
     * @param boolean $showStackTrace [optional] whether or not show the stack trace. Defaults to true.
     */
    public static function dump($var, $name = "unknown var name", $showStackTrace = true)
    {
        $firephp = \FirePHP::getInstance(true);
        $firephp->group($name, array("Collapsed" => true, "Color" => "blue"));
        $firephp->fb($var, "dump");

        // render the stack trace
        if ($showStackTrace) {
            $stackTrace = debug_backtrace(0);
            self::renderStackTrace($stackTrace);
        }

        $firephp->groupEnd();
    }

    /**
     * Renders the stack trace
     * @param array $stackTrace
     */
    protected static function renderStackTrace(array $stackTrace)
    {
        $firephp    = \FirePHP::getInstance(true);
        $digits     = strlen(max(count($stackTrace) - 1, 0));
        $table      = array();
        $table[]    = array("", "Function", "Line", "File", "Args");

        if ($stackTrace) {
            foreach ($stackTrace as $k => $v) {
                $index      = str_pad($k, $digits, "0", STR_PAD_LEFT);
                $function   = isset($v["class"]) ? "{$v["class"]}{$v["type"]}{$v["function"]}()" : "{$v["function"]}()";
                $file       = array_key_exists("file", $v) ? $v["file"] : null;
                $line       = array_key_exists("line", $v) ? $v["line"] : null;
                $table[]    = array($index, $function, $line, $file, $v['args']);
            }

            $firephp->table("Stack trace", $table);
        } else {
            // empty stack
            $firephp->log("* no stack trace *");
        }
    }
}
