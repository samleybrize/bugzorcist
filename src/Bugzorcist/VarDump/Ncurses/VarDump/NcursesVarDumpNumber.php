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
 * Ncurses number var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpNumber extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        // TODO search
        if ("integer" != $tree["type"] && "long" != $tree["type"] && "float" != $tree["type"] && "double" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected one of 'integer', 'long', 'float', 'double");
        }

        parent::__construct($tree, $parent);

        // build text array
        $str        = "<<4>>{$tree["type"]}<<0>>(<<1>>{$tree["value"]}<<0>>)";
        $strArray   = $this->buildTextArray($str);
        $this->setStringArrayCollapsed($strArray);
    }
}
