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
 * Ncurses array var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpArray extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        // TODO search
        if ("array" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'array'");
        }

        parent::__construct($tree, $parent);
        $this->setExpandable(true);

        // render array
        $isClone        = null !== $tree["refUid"];
        $strCollapsed   = "<<4>>array<<0>>(<<1>>{$tree["count"]}<<0>>) ";
        $strExpanded    = $strCollapsed;
        $strCollapsed  .= $isClone ? ">>" : "▸";
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

    /**
     * {@inheritdoc}
     */
    public function getStringArray()
    {
        $strArray = parent::getStringArray();

        // modify the color of the object id
        if ($this->isHighlightedAsReferencer()) {
            // this element is the one that point to another element
            $strArray[2] = 26;
        } elseif ($this->isHighlightedAsReferenced()) {
            // this element is the referenced element
            $strArray[2] = 27;
        }

        return $strArray;
    }
}
