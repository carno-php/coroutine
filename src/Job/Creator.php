<?php
/**
 * Job creator
 * User: moyo
 * Date: 2018/7/17
 * Time: 10:47 AM
 */

namespace Carno\Coroutine\Job;

use Carno\Coroutine\Context;
use Carno\Coroutine\Job;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Generator;
use Throwable;

class Creator
{
    /**
     * @param mixed[] $programs
     * @return array [programs, context]
     */
    public static function context(array $programs) : array
    {
        $ctx = null;

        foreach ($programs as $id => $program) {
            if ($program instanceof Context) {
                $ctx = $program;
                unset($programs[$id]);
                break;
            }
        }

        return [$programs, $ctx];
    }

    /**
     * @param mixed[] $programs
     * @param Context $ctx
     * @return Promised[]
     */
    public static function promises(array $programs, Context $ctx = null) : array
    {
        return array_map(static function ($program) use ($ctx) {
            return self::promised($program, [], $ctx);
        }, $programs);
    }

    /**
     * @param mixed $program
     * @param array $args
     * @param Context $ctx
     * @return Promised
     */
    public static function promised($program, array $args = [], Context $ctx = null) : Promised
    {
        if ($program instanceof Promised) {
            return $program;
        } elseif ($program instanceof Generator) {
            return (new Job($program, $ctx))->end();
        } elseif (is_callable($program)) {
            try {
                return self::promised(call_user_func($program, ...$args), [], $ctx);
            } catch (Throwable $e) {
                return Promise::rejected($e);
            }
        } else {
            return Promise::resolved($program);
        }
    }
}
