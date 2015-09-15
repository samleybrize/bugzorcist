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

class NcursesVarDumpString extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        // TODO unselectable lines
        // TODO search
        if ("string" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'string'");
        }

        parent::__construct($tree, $parent);
        $strExpanded        = "";
        $strCollapsed       = "";

        // build expanded string
        $strExpanded        = "<<4>>string<<0>>(<<1>>{$tree["length"]}<<0>>) <<1>>\"{$tree["value"]}\"<<0>>";

        // build collapsed string
        // limits string length if it is multiline
        $limit              = strpos($tree["value"], "\n");
        $limit              = false !== $limit ? $limit : $tree["length"];
        $string             = $tree["value"];

        if ($tree["length"] > $limit) {
            $string             = substr($tree["value"], 0, $limit) . "...";
            $strCollapsed       = "<<4>>string<<0>>(<<1>>{$tree["length"]}<<0>>) <<1>>\"$string\"<<0>> ▸";
            $this->setExpandable(true);
            $this->setStringArrayCollapsed($this->buildTextArray($strCollapsed));
            $this->setStringArrayExpanded($this->buildTextArray($strExpanded));
        } else {
            // non expandable string
            $this->setStringArrayCollapsed($this->buildTextArray($strExpanded));
        }
    }
}
