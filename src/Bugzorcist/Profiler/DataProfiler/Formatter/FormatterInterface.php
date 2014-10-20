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

interface FormatterInterface
{
    /**
     * Format a request as HTML
     * @param string $text
     * @return string
     */
    public function formatHtml($text);

    /**
     * Format a request as plain text
     * @param string $text
     * @return string
     */
    public function formatPlain($text);
}
