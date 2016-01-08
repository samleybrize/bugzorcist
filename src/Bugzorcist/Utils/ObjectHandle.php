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
 * Retrieves object handle
 * @author Stephen Berquet <stephen.berquet@gmail.com>
 */
class ObjectHandle
{
    /**
     * Offset of the relevant part of an object hash
     * @var int
     */
    private static $offset;

    /**
     * Mask used to calculate an object handle
     * @var int
     */
    private static $mask;

    /**
     * Retrieves object handle
     * @param object $object
     * @return int
     */
    public static function getObjectHandle($object)
    {
        self::init();

        if (!is_object($object)) {
            return false;
        }

        $relevantInt    = self::getObjectHashRelevantInt($object);
        $handle         = self::$mask ^ $relevantInt;

        return $handle;
    }

    /**
     * Inits class
     */
    private static function init()
    {
        if (null !== self::$offset) {
            return;
        }

        // calculate offset and mask
        self::$offset   = 16 - PHP_INT_SIZE;
        self::$mask     = -1;
        $tested         = (object) array();

        if (defined('HHVM_VERSION')) {
            // HHVM specific
            self::$offset += 16;
        } else {
            // determine the mask to apply to object hashes
            ob_start();
            debug_zval_dump($tested);
            $debug      = ob_get_clean();
            self::$mask = (int) substr($debug, strpos($debug, "#") + 1);
        }

        $relevantInt    = self::getObjectHashRelevantInt($tested);
        self::$mask    ^= $relevantInt;
    }

    /**
     * Returns the relevant int of an object hash to calculate its handle
     * @param object $object
     * @return int
     */
    private static function getObjectHashRelevantInt($object)
    {
        $relevantPart = substr(spl_object_hash($object), self::$offset, PHP_INT_SIZE);
        return hexdec($relevantPart);
    }
}
