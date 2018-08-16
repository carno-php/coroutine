<?php
/**
 * co/cmds defer
 * User: moyo
 * Date: 20/01/2018
 * Time: 3:01 PM
 */

namespace Carno\Coroutine\Tests\CMDS;

use function Carno\Coroutine\ctx;
use function Carno\Coroutine\defer;
use function Carno\Coroutine\go;
use Carno\Promise\Promise;
use PHPUnit\Framework\TestCase;

class DeferTest extends TestCase
{
    private $i = 0;

    private function ctx3()
    {
        yield defer(function () {
            $this->i ++; # 5
            $this->assertEquals(5, $this->i);
        });
        $this->i ++; # 4
        $ctx = yield ctx();
        return $ctx;
    }

    private function ctx2()
    {
        yield defer(function () {
            $this->i ++; # 6
            $this->assertEquals(6, $this->i);
        });
        $this->i ++; # 3
        $ctx = yield $this->ctx3();
        return $ctx;
    }

    private function ctx()
    {
        yield defer(function () {
            $this->i ++; # 7
            $this->assertEquals(7, $this->i);
        });
        $this->i ++; # 2
        $ctx = yield $this->ctx2();
        return $ctx;
    }

    public function testDefer()
    {
        $this->i = 0;
        $p = Promise::deferred();

        go(function () use ($p) {
            yield defer(function () {
                $this->i ++; # 9
                $this->assertEquals(9, $this->i);
            });

            $this->i ++; # 1
            yield $this->ctx();
            $this->i ++; # 8

            $this->assertEquals('yes', yield $p);
        });

        $p->resolve('yes');
    }
}
