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
 * Ncurses abstract var type
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
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
     * Unique identifier
     * @var string
     */
    private $uid;

    /**
     * UID of the referenced element
     * @var string
     */
    private $refUid;

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
     * Last known Y position
     * @var int
     */
    private $lastPosY = 0;

    /**
     * Indicates if this element is a referencer
     * @var boolean
     */
    private $highlightAsReferencer = false;

    /**
     * Indicates if this element is a referenced element
     * @var boolean
     */
    private $highlightAsReferenced = false;

    /**
     * Text to search
     * @var string|null
     */
    private $searchText;

    /**
     * String array with highlighted text search
     * @var array|null
     */
    private $strArraySearch;

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
        $this->parent   = $parent;
        $this->uid      = array_key_exists("uid", $tree) ? $tree["uid"] : null;
        $this->refUid   = array_key_exists("refUid", $tree) ? $tree["refUid"] : null;
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
            $this->isExpended = (null === $this->refUid) ? true : false;
            $this->rebuildStringArraySearch();

            // notify parent, if any
            if ($this->parent) {
                $this->parent->notifyChildModification($this);

                // expand parent
                if ($expandParents) {
                    $this->parent->expand(true);
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
        $children = null;

        if ($collapseChildren) {
            $children = $this->getChildren();

            foreach ($children as $child) {
                $child->collapse(true);
            }
        }

        // collapse element
        $this->isExpended = false;
        $this->rebuildStringArraySearch();

        // notify parent, if any
        if ($this->parent && empty($children)) {
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
     * Returns string array, depending on whether the element is expanded or not.
     * Even indexes contains color codes, odd indexes contains texts.
     * @return array
     */
    public function getStringArray()
    {
        if (null !== $this->strArraySearch) {
            return $this->strArraySearch;
        }

        return $this->isExpanded() ? $this->strArrayExpended : $this->strArrayCollapsed;
    }

    /**
     * Returns string array (collapsed state).
     * Even indexes contains color codes, odd indexes contains texts.
     * @return array
     */
    public function getStringArrayCollapsed()
    {
        return $this->strArrayCollapsed;
    }

    /**
     * Returns string array (expanded state).
     * Even indexes contains color codes, odd indexes contains texts.
     * @return array
     */
    public function getStringArrayExpanded()
    {
        return $this->strArrayExpended;
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
     * Returns cumulated string height of all children, only if expanded
     * @param boolean $ifCollapsed [optional] calculate width if element is collapsed
     * @return number
     */
    public function getChildrenHeight($ifCollapsed = false)
    {
        if ($this->isExpanded() || $ifCollapsed) {
            // expanded
            // compute and cache
            if (null === $this->childrenHeightCache) {
                $children   = $this->getChildren($ifCollapsed);
                $height     = 0;

                foreach ($children as $child) {
                    $height += $child->getStringHeight() + $child->getChildrenHeight($ifCollapsed);
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
     * Returns cumulated string width of all children, only if expanded
     * @param boolean $ifCollapsed [optional] calculate width if element is collapsed
     * @return number
     */
    public function getChildrenWidth($ifCollapsed = false)
    {
        if ($this->isExpanded() || $ifCollapsed) {
            // expanded
            // compute and cache
            if (null === $this->childrenWidthCache) {
                $children   = $this->getChildren($ifCollapsed);
                $width      = 0;

                foreach ($children as $child) {
                    $width = max(
                        $width,
                        $child->getStringWidth() + 4,
                        $child->getChildrenWidth($ifCollapsed) + 4
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
     * Returns children elements, only if expanded
     * @param boolean $ifCollapsed [optional] return children if element is collapsed
     * @return \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract[]
     */
    public function getChildren($ifCollapsed = false)
    {
        if ($this->isExpanded() || $ifCollapsed) {
            return $this->children;
        }

        return array();
    }

    /**
     * Notify that a child state has changed
     * @param \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract $child changed child
     */
    public function notifyChildModification(NcursesVarDumpTypeAbstract $child)
    {
        $this->childrenHeightCache  = null;
        $this->childrenWidthCache   = null;

        // notify parents
        if ($this->parent) {
            $this->parent->notifyChildModification($this);
        }
    }

    /**
     * Returns the UID
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Returns the UID of the referenced element
     * @return string
     */
    public function getRefUid()
    {
        return $this->refUid;
    }

    /**
     * Finds an element by its uid
     * @param string $uid
     * @return \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract|false
     */
    public function findUid($uid)
    {
        if ($this->getUid() == $uid) {
            return $this;
        }

        $children = $this->getChildren(true);

        foreach ($children as $child) {
            if (false !== ($found = $child->findUid($uid))) {
                return $found;
            }
        }

        return false;
    }

    /**
     * Returns last known Y position
     * @return int
     */
    public function getLastPosY()
    {
        return $this->lastPosY;
    }

    /**
     * Sets last known Y position
     * @param int $pos
     */
    public function setLastPosY($pos)
    {
        $this->lastPosY = (int) $pos;
    }

    /**
     * Indicates if this element is a referencer
     * @return boolean
     */
    public function isHighlightedAsReferencer()
    {
        return $this->highlightAsReferencer;
    }

    /**
     * Indicates if this element is a referenced element
     * @return boolean
     */
    public function isHighlightedAsReferenced()
    {
        return $this->highlightAsReferenced;
    }

    /**
     * Sets if this element is a referencer
     * @param boolean $highlight
     */
    public function highlightAsReferencer($highlight)
    {
        $this->highlightAsReferencer = (bool) $highlight;
        $this->rebuildStringArraySearch();
        $this->notifyChildModification($this);
    }

    /**
     * Sets if this element is a referenced element
     * @param boolean $highlight
     */
    public function highlightAsReferenced($highlight)
    {
        $this->highlightAsReferenced = (bool) $highlight;
        $this->rebuildStringArraySearch();
        $this->notifyChildModification($this);
    }

    /**
     * Search a text in element's string array and all of its children.
     * Returns all matched elements.
     * @param string $searchText text to search
     * @return \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract[]
     */
    public function searchText($searchText)
    {
        $foundElementList   = array();
        $this->searchText   = $searchText;

        // search text
        // if no match is found on expanded text then we assume no match
        // unless element is not expandable
        // this is necessary for some elements (eg: multiline string)
        $isExpandable   = $this->isExpandable();
        $strArray       = $isExpandable ? $this->getStringArrayExpanded() : $this->getStringArrayCollapsed();
        $strArraySearch = $this->searchInStringArray($strArray, $searchText);

        if ($strArraySearch !== $strArray) {
            // at least one occurence was found
            $this->strArraySearch   = $strArraySearch;
            $foundElementList[]     = $this;

            // if element is expandable but not expanded, compute its collapsed version
            if ($isExpandable && !$this->isExpanded()) {
                $this->strArraySearch = $this->searchInStringArray($this->getStringArrayCollapsed(), $searchText);
            }
        } elseif (null !== $this->strArraySearch) {
            // no occurence was found
            // clear previous search
            $this->clearSearch();
        }

        // search in children
        $children = $this->getChildren(true);

        foreach ($children as $child) {
            $foundElementList = array_merge($foundElementList, $child->searchText($searchText));
        }

        return $foundElementList;
    }

    /**
     * Clear search state of this element and all of its children
     */
    public function clearSearch()
    {
        $this->searchText       = null;
        $this->strArraySearch   = null;
        $children               = $this->getChildren(true);

        foreach ($children as $child) {
            $child->clearSearch();
        }
    }

    /**
     * Indicates if this element has found an occurence of the search text
     * @return boolean
     */
    public function hasFoundTextSearch()
    {
        return null !== $this->strArraySearch;
    }

    /**
     * Search a text in a string array
     * @param array $stringArray string array to search in
     * @param string $searchText text to search
     * @return array string array with highlighted search text
     */
    protected function searchInStringArray(array $stringArray, $searchText)
    {
        $searchStart        = 0;
        $stringInline       = $this->stringArrayToString($stringArray);
        $stringInlineLength = strlen($stringInline);

        while (
            null !== $searchText &&
            "" !== $searchText &&
            $searchStart <= $stringInlineLength &&
            false !== ($searchPos = stripos($stringInline, $searchText, $searchStart))
        ) {
            // found text
            $curPos             = 0;
            $searchLength       = strlen($searchText);
            $replaceList        = array();
            $color              = null;

            // identify pieces that match search text
            // for the text search "rt", replacement is :
            // aze[rt]y        => aze / rt / y
            // aze[r] / [t]y   => aze / rt / y
            foreach ($stringArray as $k => $text) {
                // retrieve text color
                if (null === $color) {
                    $color = $text;
                    continue;
                }

                // search and replace
                $startKey   = $k - 1;
                $tLength    = strlen($text);

                if ($searchPos >= $curPos && $searchPos < $curPos + $tLength) {
                    $found          = substr($text, $searchPos - $curPos, $searchLength);
                    $foundLength    = strlen($found);
                    $pre            = substr($text, 0, $searchPos - $curPos);
                    $post           = substr($text, $searchPos - $curPos + $foundLength);

                    if (!array_key_exists($startKey, $replaceList)) {
                        $replaceList[$startKey] = array();
                    }

                    // add text before match to replacement
                    if ($pre) {
                        $replaceList[$startKey][] = $color;
                        $replaceList[$startKey][] = $pre;
                    }

                    // add matched text
                    $replaceList[$startKey][] = 28;
                    $replaceList[$startKey][] = $found;

                    // add text after match to replacement
                    if ($post) {
                        $replaceList[$startKey][] = $color;
                        $replaceList[$startKey][] = $post;
                    }

                    // for further processing
                    $searchPos     += $foundLength;
                    $searchLength  -= $foundLength;
                }

                if ($searchLength <= 0) {
                    // there is no more text to search
                    break;
                }

                $curPos    += $tLength;
                $color      = null;
            }

            // do replacement
            krsort($replaceList);

            foreach ($replaceList as $k => $replace) {
                $stringArray  = array_merge(
                    array_slice($stringArray, 0, $k),
                    $replace,
                    array_slice($stringArray, $k + 2)
                );
            }

            // to find next occurence in same text
            $searchStart += $searchPos;
        }

        return $stringArray;
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

    /**
     * Return string array with highlighted text search
     * @return array|null
     */
    protected function getStringArraySearch()
    {
        return $this->strArraySearch;
    }

    /**
     * Rebuild string array with highlighted text search
     */
    protected function rebuildStringArraySearch()
    {
        if (null === $this->searchText || null === $this->strArraySearch) {
            return;
        }

        $this->strArraySearch = null;
        $this->strArraySearch = $this->searchInStringArray($this->getStringArray(), $this->searchText);
    }
}
