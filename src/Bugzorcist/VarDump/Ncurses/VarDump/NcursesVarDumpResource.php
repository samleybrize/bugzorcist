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
 * Ncurses resource var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class NcursesVarDumpResource extends NcursesVarDumpTypeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        // TODO search
        if ("resource" != $tree["type"]) {
            throw new \InvalidArgumentException("Invalid var tree given, expected 'resource'");
        }

        parent::__construct($tree, $parent);

        // build text array
        $str        = "<<4>>resource<<0>>({$tree["value"]})";
        $strArray   = $this->buildTextArray($str);
        $this->setStringArrayCollapsed($strArray);
    }
}
