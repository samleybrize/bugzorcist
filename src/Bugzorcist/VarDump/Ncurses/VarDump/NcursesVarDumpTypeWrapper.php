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

class NcursesVarDumpTypeWrapper extends NcursesVarDumpTypeAbstract
{
    /**
     * Wrapped object
     * @var \Bugzorcist\VarDump\Ncurses\VarDump\NcursesVarDumpTypeAbstract
     */
    private $wrapped;

    /**
     * String prefix array (collapsed/expanded)
     * @var array
     */
    private $strPrefix;

    /**
     * String prefix height
     * @var int
     */
    private $strPrefixHeight;

    /**
     * String prefix width
     * @var int
     */
    private $strPrefixWidth;

    /**
     * Cached string array
     * @var array
     */
    private $strArrayCache;

    /**
     * Cached string height
     * @var int
     */
    private $strHeightCache;

    /**
     * Cached string width
     * @var int
     */
    private $strWidthCache;

    /**
     * {@inheritdoc}
     */
    public function __construct(NcursesVarDumpTypeAbstract $wrapped, NcursesVarDumpTypeAbstract $parent = null, $strPrefix = null)
    {
        // TODO search
        parent::__construct(array(), $parent);
        $this->wrapped          = $wrapped;
        $this->strPrefix        = $this->buildTextArray($strPrefix);
        $this->strPrefixHeight  = !empty($strPrefix) ? $this->calculateStringHeight($strPrefix) : 0;
        $this->strPrefixWidth   = !empty($strPrefix) ? $this->calculateStringWidth($strPrefix) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpandable()
    {
        return $this->wrapped->isExpandable();
    }

    /**
     * {@inheritdoc}
     */
    public function isExpanded()
    {
        return $this->wrapped->isExpanded();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->wrapped->getChildren();
    }

    /**
     * {@inheritdoc}
     */
    public function expand($expandParents = false)
    {
        $this->clearCaches();
        return $this->wrapped->expand($expandParents);
    }

    /**
     * {@inheritdoc}
     */
    public function collapse($collapseChildren = false)
    {
        $this->clearCaches();
        return $this->wrapped->collapse($collapseChildren);
    }

    /**
     * {@inheritdoc}
     */
    public function notifyChildModification(NcursesVarDumpTypeAbstract $child)
    {
        $this->clearCaches();
        $this->wrapped->notifyChildModification($child);
        parent::notifyChildModification($child);
    }

    /**
     * {@inheritdoc}
     */
    public function getStringArray()
    {
        if (null === $this->strArrayCache) {
            $this->strArrayCache = array_merge($this->strPrefix, $this->wrapped->getStringArray());
        }

        return $this->strArrayCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getStringHeight()
    {
        if (null === $this->strHeightCache) {
            $this->strHeightCache = max($this->strPrefixHeight, $this->wrapped->getStringHeight());
        }

        return $this->strHeightCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getStringWidth()
    {
        if (null === $this->strWidthCache) {
            $this->strWidthCache = max($this->strPrefixWidth, $this->wrapped->getStringWidth());
        }

        return $this->strWidthCache;
    }

    /**
     * Clears caches
     */
    protected function clearCaches()
    {
        $this->strHeightCache   = null;
        $this->strArrayCache    = null;
    }
}