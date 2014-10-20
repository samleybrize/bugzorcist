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

class NcursesConfigPhp extends NcursesAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function render()
    {
        $config     = ini_get_all();
        $maxLength  = 0;

        foreach ($config as $name => $value) {
            $maxLength = max($maxLength, strlen($name));
        }

        foreach ($config as $name => $value) {
            $name = str_pad($name, $maxLength, " ", STR_PAD_RIGHT);
            $this->printText("<<3>>$name : <<0>>{$value["local_value"]}\n");
        }
    }
}
