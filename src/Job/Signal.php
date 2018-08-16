<?php
/**
 * Job signals
 * User: moyo
 * Date: 01/08/2017
 * Time: 3:47 PM
 */

namespace Carno\Coroutine\Job;

interface Signal
{
    public const SLEEP = 0xC4;
    public const KEEP = 0xC5;
    public const ROLL = 0xC6;
    public const KILL = 0xC7;
    public const FIN = 0xC9;
}
