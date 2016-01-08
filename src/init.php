<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Bugzorcist\VarDump\VarDumpFirePhp;
use Bugzorcist\VarDump\VarDumpHtml;
use Bugzorcist\VarDump\VarDumpNcurses;

// in PHP < 5.4, REQUEST_TIME_FLOAT does not exists
if (!array_key_exists("REQUEST_TIME_FLOAT", $_SERVER)) {
    $_SERVER["REQUEST_TIME_FLOAT"] = microtime(true);
}

// convenient dump function
if (!function_exists("bzdump")) {
    /**
     * Dumps a var
     * @param mixed $var var to dump
     * @param string $name [optional] var name
     */
    function bzdump($var, $name = "unknown var name")
    {
        if ("cli" == PHP_SAPI) {
            // command line
            if (extension_loaded("ncurses")) {
                // ncurses dump
                VarDumpNcurses::dump($var, $name);
            } else {
                // regular var_dump()
                var_dump($var);
            }
        } elseif (array_key_exists("HTTP_X_REQUESTED_WITH", $_SERVER) && "XMLHttpRequest" == $_SERVER["HTTP_X_REQUESTED_WITH"]) {
            // firephp dump
            VarDumpFirePhp::dump($var, $name);
        } else {
            // html dump
            VarDumpHtml::dump($var, $name);
        }
    }
}
