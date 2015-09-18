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
    public function getChildren($ifCollapsed = false)
    {
        return $this->wrapped->getChildren($ifCollapsed);
    }

    /**
     * {@inheritdoc}
     */
    public function expand($expandParents = false)
    {
        $this->clearCaches();
        $this->wrapped->expand($expandParents);
        parent::expand($expandParents);
    }

    /**
     * {@inheritdoc}
     */
    public function collapse($collapseChildren = false)
    {
        $this->clearCaches();
        $this->wrapped->collapse($collapseChildren);
        parent::collapse($collapseChildren);
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
            $this->strWidthCache = $this->strPrefixWidth + $this->wrapped->getStringWidth();
        }

        return $this->strWidthCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getRefUid()
    {
        return $this->wrapped->getRefUid();
    }

    /**
     * {@inheritdoc}
     */
    public function getUid()
    {
        return $this->wrapped->getUid();
    }

    /**
     * {@inheritdoc}
     */
    public function isHighlightedAsReference()
    {
        return $this->wrapped->isHighlightedAsReference();
    }

    /**
     * {@inheritdoc}
     */
    public function isHighlightedAsReferenced()
    {
        return $this->wrapped->isHighlightedAsReferenced();
    }

    /**
     * {@inheritdoc}
     */
    public function highlightAsReferencer($highlight)
    {
        $this->wrapped->highlightAsReferencer($highlight);
        $this->notifyChildModification($this);
    }

    /**
     * {@inheritdoc}
     */
    public function highlightAsReferenced($highlight)
    {
        $this->wrapped->highlightAsReferenced($highlight);
        $this->notifyChildModification($this);
    }

    /**
     * Clears caches
     */
    protected function clearCaches()
    {
        $this->strHeightCache   = null;
        $this->strWidthCache    = null;
        $this->strArrayCache    = null;
    }
}
