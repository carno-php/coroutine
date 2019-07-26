<?php
/**
 * Commands kit
 * User: moyo
 * Date: 07/08/2017
 * Time: 6:18 PM
 */

namespace Carno\Coroutine;

use Carno\Coroutine\Exception\TimeoutException;
use Carno\Coroutine\Job\Creator;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Timer\Timer;
use Closure;
use Throwable;

/**
 * @param $ms
 * @param string $ec
 * @param string $em
 * @return Promised
 */
function timeout(
    int $ms,
    string $ec = TimeoutException::class,
    string $em = ''
) : Promised {
    $racer = Promise::deferred();
    if ($ms > 0) {
        $timer = Timer::after($ms, static function () use ($racer, $ec, $em) {
            $racer->throw(new $ec($em));
        });
        $racer->catch(static function () use ($timer) {
            Timer::clear($timer);
        });
    }
    return $racer;
}

/**
 * sleep [and do something] (optional)
 * @param int $ms
 * @param Closure $do
 * @return Promised
 */
function msleep(
    int $ms,
    Closure $do = null
) : Promised {
    return new Promise(static function (Promised $promised) use ($ms, $do) {
        $tick = Timer::after($ms, static function () use ($promised, $do) {
            $promised->resolve($do ? $do() : null);
        });
        $promised->catch(static function () use ($tick) {
            Timer::clear($tick);
        });
    });
}

/**
 * "GO" new program without results but throws exception if error
 * @param mixed $program
 * @param Context $ctx
 */
function go($program, Context $ctx = null) : void
{
    async($program, $ctx)->fusion();
}

/**
 * similar with "GO" but returns "CLOSURE"
 * @param mixed $program
 * @param Context $ctx
 * @return Closure
 */
function co($program, Context $ctx = null) : Closure
{
    return static function (...$args) use ($program, $ctx) {
        return async($program, $ctx, ...$args)->fusion();
    };
}

/**
 * similar with "GO" but returns "PROMISED" and no "THROWS"
 * @param mixed $program
 * @param Context $ctx
 * @param mixed $args
 * @return Promised
 */
function async($program, Context $ctx = null, ...$args) : Promised
{
    return Creator::promised($program, $args, $ctx);
}

/**
 * wait and wake programme
 * @param Closure $dial
 * @param Closure $awake
 * @param int $timeout
 * @param string $error
 * @param string $message
 * @return Promised
 */
function await(
    Closure $dial,
    Closure $awake,
    int $timeout = 60000,
    string $error = TimeoutException::class,
    string $message = ''
) : Promised {
    return race(
        new Promise(static function (Promised $await) use ($dial, $awake) {
            $dial(static function (...$args) use ($await, $awake) {
                try {
                    $await->pended() && $out = $awake(...$args);
                    $await->pended() && $await->resolve($out ?? null);
                } catch (Throwable $e) {
                    $await->pended() && $await->throw($e);
                }
            });
        }),
        timeout($timeout, $error, $message)
    );
}

/**
 * @return Syscall
 */
function ctx() : Syscall
{
    return new Syscall(static function (Job $job) {
        return $job->ctx();
    });
}

/**
 * @param Closure $program
 * @return Syscall
 */
function defer(Closure $program) : Syscall
{
    return new Syscall(static function (Job $job) use ($program) {
        $job->roll()->then(static function ($stage) use ($job, $program) {
            co($program, $job->ctx())($stage);
        });
    });
}

/**
 * @param mixed ...$programs
 * @return Promised
 */
function race(...$programs) : Promised
{
    return Promise::race(...Creator::promises(...Creator::context($programs)));
}

/**
 * @param mixed ...$programs
 * @return Promised
 */
function all(...$programs) : Promised
{
    return Promise::all(...Creator::promises(...Creator::context($programs)));
}
