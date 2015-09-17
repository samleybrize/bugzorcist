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

class NcursesVarDumpArray extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        // TODO search
        if ("array" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'string'");
        }

        parent::__construct($tree, $parent);
        $this->setExpandable(true);

        // render array
        $strCollapsed   = "<<4>>array<<0>>(<<1>>{$tree["count"]}<<0>>) ";
        $strExpanded    = $strCollapsed;
        $strCollapsed  .= "▸";
        $strExpanded   .= "▾";

        $this->setStringArrayCollapsed($this->buildTextArray($strCollapsed));
        $this->setStringArrayExpanded($this->buildTextArray($strExpanded));

        // add children
        foreach ($tree["children"] as $k => $subTree) {
            $key        = "[$k] = ";
            $child      = NcursesVarDumpTypeAbstract::factory($subTree);
            $wrapper    = new NcursesVarDumpTypeWrapper($child, $this, $key);
            $this->addChild($wrapper);
        }
    }
}
