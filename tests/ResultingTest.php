<?php
/**
 * Resulting tests
 * User: moyo
 * Date: 2018/4/4
 * Time: 1:34 PM
 */

namespace Carno\Coroutine\Tests;

use function Carno\Coroutine\go;
use PHPUnit\Framework\TestCase;

class ResultingTest extends TestCase
{
    public function testReturn1()
    {
        $func = function ($in) {
            $c = 1;
            if ($in) {
                $c = yield 2;
            }
            return $c;
        };

        go(function () use ($func) {
            $r = yield $func(0);
            $this->assertEquals(1, $r);
            $r = yield $func(1);
            $this->assertEquals(2, $r);
        });
    }

    public function testYield1()
    {
        $func = function ($in) {
            $c = 1;
            if ($in) {
                $c = yield 2;
            }
            yield $c;
        };

        go(function () use ($func) {
            $r = yield $func(0);
            $this->assertEquals(null, $r);
            $r = yield $func(1);
            $this->assertEquals(null, $r);
        });
    }

    public function testYield2()
    {
        $func = function ($in) {
            switch ($in) {
                case 0:
                    return 0;
                case 1:
                    return yield false;
                case 2:
                    return [];
                case 3:
                    return yield null;
            }
        };

        go(function () use ($func) {
            $this->assertTrue(0 === yield $func(0));
            $this->assertTrue(false === yield $func(1));
            $this->assertTrue([] === yield $func(2));
            $this->assertTrue(null === yield $func(3));
            $this->assertTrue(null === yield $func(9));
        });
    }

    public function testIterators()
    {
        $fn = function ($int) {
            while ($int > 0) {
                yield 'IG' => $int--;
            }
        };

        go(function (int $start = 3) use ($fn) {
            foreach ($fn($start) as $got) {
                $this->assertEquals($start--, $got);
            }
            $this->assertEquals(0, $start);
        });
    }
}
