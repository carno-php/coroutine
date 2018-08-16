<?php
/**
 * Job test class
 * User: moyo
 * Date: 2018/7/16
 * Time: 10:48 AM
 */

namespace Carno\Coroutine\Tests;

class JTClass
{
    private $src = null;

    public function __construct(&$src)
    {
        $this->src = &$src;
    }

    public function __destruct()
    {
        $this->src = 1;
    }
}
