<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Profiler\DataProfiler\Formatter;

/**
 * Data query text default formatter
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class FormatterDefault implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function formatHtml($text)
    {
        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function formatPlain($text)
    {
        return $text;
    }
}
