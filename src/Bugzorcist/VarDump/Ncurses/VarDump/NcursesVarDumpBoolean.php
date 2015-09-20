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
 * Ncurses boolean var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpBoolean extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        if ("bool" != $tree["type"] && "boolean" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected one of 'bool' or 'boolean'");
        }

        parent::__construct($tree, $parent);

        // build text array
        $str        = "<<4>>bool<<0>>(<<2>>{$tree["value"]}<<0>>)";
        $strArray   = $this->buildTextArray($str);
        $this->setStringArrayCollapsed($strArray);
    }
}
