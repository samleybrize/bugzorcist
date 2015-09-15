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

// TODO comment
abstract class NcursesVarDumpTypeAbstract
{
    /**
     * Children elements
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract[]
     */
    private $children = array();

    /**
     * Parent element
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     */
    private $parent;

    /**
     * Indicates if this element is expandable
     * @var boolean
     */
    private $isExpendable = false;

    /**
     * Indicates if this element is expanded
     * @var boolean
     */
    private $isExpended = false;

    /**
     * String array (collapsed)
     * @var array
     */
    private $strArrayCollapsed;

    /**
     * String array (expanded)
     * @var array
     */
    private $strArrayExpended;

    /**
     * String height (collapsed)
     * @var int
     */
    private $strHeightCollapsed;

    /**
     * String width (collapsed)
     * @var int
     */
    private $strWidthCollapsed;

    /**
     * String height (expanded)
     * @var int
     */
    private $strHeightExpanded;

    /**
     * String width (expanded)
     * @var int
     */
    private $strWidthExpanded;

    /**
     * Children cached string height
     * @var int
     */
    private $childrenHeightCache;

    /**
     * Children cached string width
     * @var int
     */
    private $childrenWidthCache;

    /**
     * Creates a var type object based on a var tree
     * @param array $tree
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $parent [optional] parent element
     * @return \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     * @throws \UnexpectedValueException
     */
    public static function factory(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        switch ($tree["type"]) {
            // string
            case "string":
                return new NcursesVarDumpString($tree, $parent);
                break;

            // number
            case "integer":
            case "long":
            case "float":
            case "double":
                return new NcursesVarDumpNumber($tree, $parent);
                break;

            // boolean
            case "bool":
            case "boolean":
                return new NcursesVarDumpBoolean($tree, $parent);
                break;

            // null
            case "null":
            case "NULL":
                return new NcursesVarDumpNull($tree, $parent);
                break;

            // resource
            case "resource":
                return new NcursesVarDumpResource($tree, $parent);
                break;

            // array
            case "array":
                return new NcursesVarDumpArray($tree, $parent);
                break;

            // object
            case "object":
                return new NcursesVarDumpObject($tree, $parent);
                break;

            // unknown type
            default:
                throw new \UnexpectedValueException("Unknown var type '{$tree["type"]}'");
        }
    }

    /**
     * Constructor
     * @param array $tree var tree
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $parent [optional] parent element
     * @throws \InvalidArgumentException
     */
    public function __construct(array $tree, NcursesVarDumpTypeAbstract $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Indicates if this element is expandable
     * @return boolean
     */
    public function isExpandable()
    {
        return $this->isExpendable;
    }

    /**
     * Indicates if this element is expanded
     * @return boolean
     */
    public function isExpanded()
    {
        return $this->isExpended;
    }

    /**
     * Expands element
     */
    public function expand($expandParents = false)
    {
        if ($this->isExpandable()) {
            $this->isExpended = true;

            // notify parent, if any
            if ($this->parent) {
                $this->parent->notifyChildModification($this);

                // expand parent
                if ($expandParents) {
                    $this->parent->expand();
                }
            }
        }
    }

    /**
     * Collapses element
     * @param boolean $collapseChildren [optional] whether or not collapse child elements
     */
    public function collapse($collapseChildren = false)
    {
        // collapse child elements
        if ($collapseChildren) {
            $children = $this->getChildren();

            foreach ($children as $child) {
                $child->collapse(true);
            }
        }

        // collapse element
        $this->isExpended = false;

        // notify parent, if any
        if ($this->parent) {
            $this->parent->notifyChildModification($this);
        }
    }

    /**
     * Toggle expand/collapse
     */
    public function toggleExpand()
    {
        if ($this->isExpanded()) {
            $this->collapse(true);
        } else {
            $this->expand();
        }
    }

    /**
     * Returns string array.
     * Even indexes contains color codes, odd indexes contains texts.
     * @return array
     */
    public function getStringArray()
    {
        return $this->isExpanded() ? $this->strArrayExpended : $this->strArrayCollapsed;
    }

    /**
     * Returns string height (number of lines)
     * @return int
     */
    public function getStringHeight()
    {
        return $this->isExpanded() ? $this->strHeightExpanded : $this->strHeightCollapsed;
    }

    /**
     * Returns string width (number of columns)
     * @return int
     */
    public function getStringWidth()
    {
        return $this->isExpanded() ? $this->strWidthExpanded : $this->strWidthCollapsed;
    }

    /**
     * Returns cumulated string height of all children
     * @return number
     */
    public function getChildrenHeight()
    {
        if ($this->isExpanded()) {
            // expanded
            // compute and cache
            if (null === $this->childrenHeightCache) {
                $children   = $this->getChildren();
                $height     = 1;

                foreach ($children as $child) {
                    $height += $child->getStringHeight() + $child->getChildrenHeight();
                }

                $this->childrenHeightCache = $height;
            }

            return $this->childrenHeightCache;
        } else {
            // collapsed
            return 0;
        }
    }

    /**
     * Returns cumulated string width of all children
     * @return number
     */
    public function getChildrenWidth()
    {
        if ($this->isExpanded()) {
            // expanded
            // compute and cache
            if (null === $this->childrenWidthCache) {
                $children   = $this->getChildren();
                $width      = 0;

                foreach ($children as $child) {
                    $width = max(
                        $width,
                        $child->getStringWidth() + 4,
                        $child->getChildrenWidth() + 4
                    );
                }

                $this->childrenWidthCache = $width;
            }

            return $this->childrenWidthCache;
        } else {
            // collapsed
            return 0;
        }
    }

    /**
     * Returns children elements
     * @return \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Notify that a child state has changed
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $child changed child
     */
    public function notifyChildModification(NcursesVarDumpTypeAbstract $child)
    {
        $this->childrenHeightCache  = null;
        $this->childrenWidthCache   = null;

        if ($this->parent) {
            $this->parent->notifyChildModification($this);
        }
    }

    /**
     * Build string array
     * @param string $text
     * @return array
     */
    protected function buildTextArray($text)
    {
        // search for color tags
        $matches    = array();
        $pattern    = "#<<[0-9]+>>#";
        preg_match_all($pattern, $text, $matches);
        $matches    = $matches[0];

        $text       = preg_split($pattern, $text);
        $text       = array_reverse(array_reverse($text));

        // if the text does not begin with a color tag, we add one with default color
        if (count($text) > count($matches)) {
            array_unshift($matches, "<<0>>");
        } else {
            // remove text first piece if empty
            if ("" == current($text)) {
                array_shift($text);
            }
        }

        // build text array
        $textArray = array();

        foreach ($text as $k => $str) {
            $color          = array_key_exists($k, $matches) ? (int) substr($matches[$k], 2) : 0;
            $textArray[]    = $color;
            $textArray[]    = $str;
        }

        return $textArray;
    }

    /**
     * Returns the string representation of a string array
     * @param array $strArray
     * @return string
     */
    protected function stringArrayToString(array $strArray)
    {
        $keys   = range(1, count($strArray), 2);
        $keys   = array_flip($keys);
        $str    = implode("", array_intersect_key($strArray, $keys));

        return $str;
    }

    /**
     * Calculate the height (number of lines) of a string
     * @param string $str
     * @return int
     */
    protected function calculateStringHeight($str)
    {
        return substr_count($str, "\n") + 1;
    }

    /**
     * Calculate the width (number of columns) of a string
     * @param string $str
     * @return int
     */
    protected function calculateStringWidth($str)
    {
        $lines  = explode("\n", $str);
        $width  = 0;

        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        return $width;
    }

    /**
     * Defines if this element is expandable
     * @param boolean $expandable
     */
    protected function setExpandable($expandable)
    {
        $this->isExpendable = (boolean) $expandable;
    }

    /**
     * Sets string array (collapsed)
     * @param array $strArray
     * @throws \InvalidArgumentException
     */
    protected function setStringArrayCollapsed(array $strArray)
    {
        if (0 != count($strArray) % 2) {
            throw new \InvalidArgumentException("Array size must be pair");
        }

        $this->strArrayCollapsed    = $strArray;
        $str                        = $this->stringArrayToString($strArray);
        $this->setStringHeightCollapsed($this->calculateStringHeight($str));
        $this->setStringWidthCollapsed($this->calculateStringWidth($str));
    }

    /**
     * Sets string array (expanded)
     * @param array $strArray
     * @throws \InvalidArgumentException
     */
    protected function setStringArrayExpanded(array $strArray)
    {
        if (0 != count($strArray) % 2) {
            throw new \InvalidArgumentException("Array size must be pair");
        }

        $this->strArrayExpended     = $strArray;
        $str                        = $this->stringArrayToString($strArray);
        $this->setStringHeightExpanded($this->calculateStringHeight($str));
        $this->setStringWidthExpanded($this->calculateStringWidth($str));
    }

    /**
     * Sets string height (expanded)
     * @param int $height
     * @throws \InvalidArgumentException
     */
    protected function setStringHeightCollapsed($height)
    {
        if ($height <= 0) {
            throw new \InvalidArgumentException("Height must be higher than 0");
        }

        $this->strHeightCollapsed = (int) $height;
    }

    /**
     * Sets string width (expanded)
     * @param int $width
     * @throws \InvalidArgumentException
     */
    protected function setStringWidthCollapsed($width)
    {
        if ($width <= 0) {
            throw new \InvalidArgumentException("Width must be higher than 0");
        }

        $this->strWidthCollapsed = (int) $width;
    }

    /**
     * Sets string height (expanded)
     * @param int $height
     * @throws \InvalidArgumentException
     */
    protected function setStringHeightExpanded($height)
    {
        if ($height <= 0) {
            throw new \InvalidArgumentException("Height must be higher than 0");
        }

        $this->strHeightExpanded = (int) $height;
    }

    /**
     * Sets string width (expanded)
     * @param int $width
     * @throws \InvalidArgumentException
     */
    protected function setStringWidthExpanded($width)
    {
        if ($width <= 0) {
            throw new \InvalidArgumentException("Width must be higher than 0");
        }

        $this->strWidthExpanded = (int) $width;
    }

    /**
     * Adds a child element
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $child
     */
    protected function addChild(NcursesVarDumpTypeAbstract $child)
    {
        $this->children[] = $child;
    }
}
