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

class FormatterSql implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function formatHtml($text)
    {
        return \SqlFormatter::format($text);
    }

    /**
     * {@inheritdoc}
     */
    public function formatPlain($text)
    {
        return strip_tags(\SqlFormatter::format($text));
    }
}
