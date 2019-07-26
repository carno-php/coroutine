<?php
/**
 * ID generator
 * User: moyo
 * Date: 01/08/2017
 * Time: 4:08 PM
 */

namespace Carno\Coroutine\Job;

class IDGen
{
    /**
     * @var int
     */
    private static $current = 0;

    /**
     * @return int
     */
    public static function next() : int
    {
        return self::$current++ >= PHP_INT_MAX ? self::$current = 1 : self::$current;
    }
}
