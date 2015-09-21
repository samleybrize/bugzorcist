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
 * Ncurses object var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpObject extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        if ("object" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'object'");
        }

        parent::__construct($tree, $parent);
        $this->setExpandable(true);

        // render object
        $isClone        = null !== $tree["refUid"];
        $rightArrow     = $isClone ? ">>" : "▸";
        $strCollapsed   = "<<4>>object<<0>>(<<5>>{$tree["class"]}<<0>>)";
        $strCollapsed  .= "<<6>>#{$tree["id"]}<<0>> (<<1>>{$tree["count"]}<<0>>) ";
        $strExpanded    = $strCollapsed;
        $strCollapsed  .= $rightArrow;
        $strExpanded   .= "▾";

        $this->setStringArrayCollapsed($this->buildTextArray($strCollapsed));
        $this->setStringArrayExpanded($this->buildTextArray($strExpanded));

        // add children
        if ($isClone) {
            return;
        }

        foreach ($tree["properties"] as $subTree) {
            $class      = $subTree["class"] ? "<<5>>{$subTree["class"]}:<<0>>" : "";
            $key        = $subTree["static"] ?
                "<<2>>static:<<3>>{$subTree["access"]}:<<0>>{$class}{$subTree["name"]}" :
                "<<3>>{$subTree["access"]}:<<0>>{$subTree["name"]}"
            ;
            $key        = "[$key] = ";
            $child      = NcursesVarDumpTypeAbstract::factory($subTree["value"]);
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

        if (!$this->hasFoundTextSearch()) {
            $strArray = $this->highlightReference($strArray);
        }

        return $strArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getStringArrayCollapsed()
    {
        return $this->highlightReference(parent::getStringArrayCollapsed());
    }

    /**
     * {@inheritdoc}
     */
    public function getStringArrayExpanded()
    {
        return $this->highlightReference(parent::getStringArrayExpanded());
    }

    /**
     * Highlights reference id in a string array
     * @param array $strArray
     * @return array
     */
    protected function highlightReference(array $strArray)
    {
        // modify the color of the object id
        if ($this->isHighlightedAsReferencer()) {
            // this element is the one that point to another element
            $strArray[10] = 26;
        } elseif ($this->isHighlightedAsReferenced()) {
            // this element is the referenced element
            $strArray[10] = 27;
        }

        return $strArray;
    }
}
