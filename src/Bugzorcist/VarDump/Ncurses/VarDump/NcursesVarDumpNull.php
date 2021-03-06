<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\VarDump\Ncurses\VarDump;

/**
 * Ncurses null var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpNull extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        if ("null" != $tree["type"] && "NULL" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'null'");
        }

        parent::__construct($tree, $parent);

        // build text array
        $str        = "<<2>>null";
        $strArray   = $this->buildTextArray($str);
        $this->setStringArrayCollapsed($strArray);
    }
}
