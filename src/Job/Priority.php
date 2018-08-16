<?php
/**
 * Job priority
 * User: moyo
 * Date: 2018/7/16
 * Time: 11:46 AM
 */

namespace Carno\Coroutine\Job;

interface Priority
{
    public const URGENT = 0xA3;
    public const HIGH = 0xA4;
    public const MEDIUM = 0xA5;
    public const LOW = 0xA6;
}
