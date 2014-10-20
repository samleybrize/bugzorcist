<?php

/*
 * This file is part of Bugzorcist.
 *
 * (c) Stephen Berquet <stephen.berquet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bugzorcist\Utils;

/**
 * Format units
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class UnitFormatter
{
    /**
     * Units
     * @var array
     */
    private static $units = array("b", "kb", "mb", "gb", "tb", "pb", "eb", "zb", "yb");

    /**
     * Convert a number of bytes to its human readable string representation
     * @param int $bytes number of bytes
     * @return string
     */
    public static function formatByte($bytes)
    {
        $unit       = (int) ((strlen($bytes) - 1) / 3);
        $formatted  = $unit ? $bytes / pow(1024, $unit) : $bytes;
        $formatted  = number_format($formatted, 2, ".", " ");
        $formatted .= " " . self::$units[$unit];

        return $formatted;
    }
}
