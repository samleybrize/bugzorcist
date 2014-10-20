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

/**
 * Ncurses user constants viewer
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesUserConstants extends NcursesAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $constants = get_defined_constants(true);

        if (!empty($constants["user"])) {
            $maxLength = 0;

            foreach ($constants["user"] as $name => $value) {
                $maxLength = max($maxLength, strlen($name));
            }

            foreach ($constants["user"] as $name => $value) {
                $name = str_pad($name, $maxLength, " ", STR_PAD_RIGHT);

                switch (gettype($value)) {
                    case "string":
                        $value  = str_replace(
                            array("\n", "\r", "\t"),
                            array("\\n", "\\r", "\\t"),
                            $value
                        );
                        break;

                    case "bool":
                    case "boolean":
                        $value = $value ? "true" : "false";
                        $value = "<<2>>$value";
                        break;

                    case "null":
                    case "NULL":
                        $value = "<<2>>null";
                        break;
                }

                $this->printText("<<3>>$name : <<0>>$value\n");
            }
        } else {
            // no user constants
            $this->printText("No user constants");
        }
    }
}
