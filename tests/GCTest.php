<?php
/**
 * GC test
 * User: moyo
 * Date: 16/03/2018
 * Time: 3:19 PM
 */

namespace Carno\Coroutine\Tests;

use function Carno\Coroutine\async;
use function Carno\Coroutine\co;
use Carno\Coroutine\Context;
use function Carno\Coroutine\ctx;
use function Carno\Coroutine\go;
use Carno\Coroutine\Stats as COStats;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Promise\Stats as POStats;
use PHPUnit\Framework\TestCase;

class GCTest extends TestCase
{
    private function ctxShouldClear()
    {
        $this->assertEquals(0, COStats::running(), 'coroutine running');
        $this->assertEquals(0, POStats::pending(), 'promise pending');
    }

    public function testGo1()
    {
        $this->ctxShouldClear();

        go(function () {
            return 'hello';
        });

        $this->ctxShouldClear();

        try {
            go(function () {
                throw new \Exception('test222');
            });
            $this->fail('You should not reached here');
        } catch (\Throwable $e) {
            $this->assertEquals('test222', $e->getMessage());
        }

        $this->ctxShouldClear();

        go(function () {
            /**
             * @var Context $ctx
             */
            $ctx = yield ctx();
            $ctx->set('hello', 'world');
            $this->assertEquals('world', $ctx->get('hello'));
        });

        $this->ctxShouldClear();

        $class1 = new class ($defer1 = Promise::deferred()) {
            private $defer = null;

            public function __construct(Promised $defer)
            {
                $this->defer = $defer;
            }

            public function s1()
            {
                return yield $this->s2();
            }

            public function s2()
            {
                return yield $this->s3();
            }

            public function s3()
            {
                return $this->defer;
            }
        };

        co(function ($class) {
            $this->assertEquals('lala', yield $class->s1());
        })($class1);

        $this->assertEquals(1, COStats::running());
        $this->assertEquals(5, POStats::pending());

        $defer1->resolve('lala');

        $class1 = null;
        $defer1 = null;

        $this->ctxShouldClear();

        $class2 = new class ($defer2 = Promise::deferred()) {
            private $defer = null;

            public function __construct(Promised $defer)
            {
                $this->defer = $defer;
            }

            public function test()
            {
                return $this->defer;
            }
        };

        co(function ($class) {
            try {
                yield $class->test();
            } catch (\Throwable $e) {
                $this->assertEquals('haha', $e->getMessage());
            }
        })($class2);

        $this->assertEquals(1, COStats::running());
        $this->assertEquals(5, POStats::pending());

        $defer2->throw(new \Exception('haha'));

        $class2 = null;
        $defer2 = null;

        $this->ctxShouldClear();

        $defer3 = Promise::deferred();

        go(function () use ($defer3) {
            co(function (Promised $defer) {
                co(function (Promised $defer) {
                    $this->assertEquals('test', yield $defer);
                })($defer);
            })($defer3);
        });

        $this->assertEquals(1, COStats::running());
        $this->assertEquals(5, POStats::pending());

        $defer3->resolve('test');

        $defer3 = null;

        $this->ctxShouldClear();
    }

    public function testGo2()
    {
        $this->ctxShouldClear();

        $defer = Promise::deferred();

        $em = '';

        go(function () use ($defer) {
            yield $defer;
            throw new \Exception('test111');
        });

        try {
            $defer->resolve();
        } catch (\Throwable $e) {
            $em = $e->getMessage();
            $e = null;
        }

        $this->assertEquals('test111', $em);

        $defer = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->ctxShouldClear();
    }

    public function testAsync()
    {
        $this->ctxShouldClear();

        $defer = Promise::deferred();

        $em = '';

        async(function () use ($defer) {
            yield $defer;
            throw new \Exception('test222');
        })->catch(function (\Throwable $e) use (&$em) {
            $em = $e->getMessage();
            $e = null;
        });

        $defer->resolve();

        $this->assertEquals('test222', $em);

        $defer = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->ctxShouldClear();
    }
}
