<?php
/**
 * Jobs test
 * User: moyo
 * Date: 2018/7/13
 * Time: 6:12 PM
 */

namespace Carno\Coroutine\Tests;

use function Carno\Coroutine\async;
use function Carno\Coroutine\await;
use function Carno\Coroutine\defer;
use Carno\Coroutine\Exception\TimeoutException;
use Carno\Coroutine\Stats as COStats;
use Carno\Promise\Promise;
use Carno\Promise\Stats as POStats;
use PHPUnit\Framework\TestCase;

class JobsTest extends TestCase
{
    public function testKill()
    {
        $d1 = $d2 = $d3 = null;
        $a = $b = null;
        $x1 = $x2 = $x3 = null;

        $to1 = Promise::deferred();
        $to2 = Promise::deferred();
        $to3 = Promise::deferred();

        $job = async(function () use (&$a, &$b, &$d1, &$d2, &$d3, &$x1, &$x2, &$x3, $to1, $to2, $to3) {
            $ca = new JTClass($a);
            yield defer(function () use ($ca, &$d1) {
                $d1 = 1;
            });
            yield defer(function () use (&$d3) {
                $d3 = 1;
            });
            $cb = new JTClass($b);
            yield (function () use ($cb, &$d2, &$x1, &$x2, &$x3, $to2, $to3) {
                yield defer(function () use ($cb, &$d2) {
                    $d2 = 1;
                });
                yield await(function ($fn) use ($to3) {
                    $to3->then($fn);
                }, function () use (&$x3) {
                    $x3 = 1;
                });
                try {
                    yield await(function ($fn) use ($to2) {
                        $to2->then($fn);
                    }, function () use (&$x1) {
                        $x1 = 1;
                    }, 0);
                } catch (TimeoutException $e) {
                    $x2 = 1;
                }
            })();
            yield $to1;
        });

        $to3->resolve();

        $job->throw(new TimeoutException());

        $to2->resolve();

        $this->assertEquals(1, $d1);
        $this->assertEquals(1, $d2);
        $this->assertEquals(1, $d3);
        $this->assertEquals(1, $a);
        $this->assertEquals(1, $b);
        $this->assertEquals(null, $x1);
        $this->assertEquals(1, $x2);
        $this->assertEquals(1, $x3);

        unset($job, $to1, $to2, $to3);

        $this->assertEquals(0, COStats::running());
        $this->assertEquals(0, POStats::pending());
    }
}
