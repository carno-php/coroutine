<?php
/**
 * co/cmds go
 * User: moyo
 * Date: 2018/4/11
 * Time: 8:42 PM
 */

namespace Carno\Coroutine\Tests\CMDS;

use function Carno\Coroutine\go;
use PHPUnit\Framework\TestCase;

class GoTest extends TestCase
{
    public function testException()
    {
        $em = '';

        try {
            go(function () {
                throw new \Exception('test222');
            });
        } catch (\Throwable $e) {
            $em = $e->getMessage();
        }

        $this->assertEquals('test222', $em);
    }
}
