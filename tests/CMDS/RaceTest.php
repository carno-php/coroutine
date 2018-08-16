<?php
/**
 * co/cmds race
 * User: moyo
 * Date: 2018/4/3
 * Time: 11:21 AM
 */

namespace Carno\Coroutine\Tests\CMDS;

use function Carno\Coroutine\async;
use function Carno\Coroutine\race;
use Carno\Coroutine\Stats as COStats;
use Carno\Promise\Promise;
use Carno\Promise\Stats as POStats;
use PHPUnit\Framework\TestCase;

class RaceTest extends TestCase
{
    public function testRace1()
    {
        $rs1 = '';
        $em1 = '';

        race(async(function () {
            return 'hello';
        }), async(function () {
            throw new \Exception('world');
        }))->then(function ($got) use (&$rs1) {
            $rs1 = $got;
        })->catch(function (\Throwable $e) use (&$em1) {
            $em1 = $e->getMessage();
        });

        $this->assertEquals('hello', $rs1);
        $this->assertEquals('', $em1);

        $rs2 = '';
        $em2 = '';

        race(async(function () {
            throw new \Exception('world');
        }), async(function () {
            return 'hello';
        }))->then(function ($got) use (&$rs2) {
            $rs2 = $got;
        })->catch(function (\Throwable $e) use (&$em2) {
            $em2 = $e->getMessage();
        });

        $this->assertEquals('', $rs2);
        $this->assertEquals('world', $em2);

        $this->assertEquals(0, COStats::running());
        $this->assertEquals(0, POStats::pending());
    }

    public function testRace2()
    {
        $s1 = Promise::deferred();
        $f1 = Promise::deferred();

        $rs1 = '';
        $em1 = '';

        $this->genRace($s1, $f1, $rs1, $em1);

        $s1->resolve();

        $this->assertEquals('hello', $rs1);
        $this->assertEquals('', $em1);

        $s2 = Promise::deferred();
        $f2 = Promise::deferred();

        $rs2 = '';
        $em2 = '';

        $this->genRace($s2, $f2, $rs2, $em2);

        $f2->resolve();

        $this->assertEquals('', $rs2);
        $this->assertEquals('world', $em2);

        $s1 = $f1 = $s2 = $f2 = null;

        $this->assertTrue(gc_collect_cycles() > 0);

        $this->assertEquals(0, COStats::running());
        $this->assertEquals(0, POStats::pending());
    }

    private function genRace($success, $failure, &$result, &$em)
    {
        race(async(function () use ($success) {
            yield $success;
            return 'hello';
        }), async(function () use ($failure) {
            yield $failure;
            throw new \Exception('world');
        }))->then(function ($got) use (&$result) {
            $result = $got;
        })->catch(function (\Throwable $e) use (&$em) {
            $em = $e->getMessage();
        });
    }
}
