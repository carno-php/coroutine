<?php
/**
 * Singleton
 * User: moyo
 * Date: 06/08/2017
 * Time: 2:01 PM
 */

namespace Carno\Coroutine\Worker;

use Carno\Coroutine\Job;
use Carno\Coroutine\Worker;

trait Singleton
{
    /**
     * @var Worker
     */
    private static $wkIns = null;

    /**
     * @param Job $job
     */
    public static function process(Job $job) : void
    {
        (self::$wkIns ?? self::$wkIns = new Worker)->accept($job)->run();
    }
}
