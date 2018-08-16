<?php
/**
 * Coroutine stats
 * User: moyo
 * Date: 13/03/2018
 * Time: 11:02 AM
 */

namespace Carno\Coroutine;

class Stats
{
    /**
     * @var int
     */
    private static $running = 0;

    /**
     * @return int
     */
    public static function running() : int
    {
        return self::$running;
    }

    /**
     */
    public static function created() : void
    {
        self::$running ++;
    }

    /**
     */
    public static function finished() : void
    {
        self::$running --;
    }
}
