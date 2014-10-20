<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump\Ncurses;

/**
 * Ncurses viewer interface
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
interface NcursesInterface
{
    /**
     * Deletes the ncurses pad
     */
    public function clearPad();

    /**
     * Key press event handler
     * @param int $keyCode code of the pressed key
     */
    public function onKeyPress($keyCode);

    /**
     * Refresh the content and write it to the main screen
     */
    public function refresh();
}
