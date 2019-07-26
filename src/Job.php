<?php
/**
 * Job core
 * User: moyo
 * Date: 01/08/2017
 * Time: 2:54 PM
 */

namespace Carno\Coroutine;

use Carno\Coroutine\Exception\InterruptException;
use Carno\Coroutine\Exception\RejectedException;
use Carno\Coroutine\Job\IDGen;
use Carno\Coroutine\Job\Priority;
use Carno\Coroutine\Job\Signal;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Generator;
use SplStack;
use Throwable;

class Job
{
    /**
     * @var int
     */
    private $p = 0;

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var Generator
     */
    private $co = null;

    /**
     * @var Context
     */
    private $ctx = null;

    /**
     * @var SplStack
     */
    private $chain = null;

    /**
     * ROLL means current co finished (e.g. function returned)
     * @var Promised[]
     */
    private $rollers = null;

    /**
     * END means all co in job are finished
     * @var Promised
     */
    private $ender = null;

    /**
     * @var bool
     */
    private $started = false;

    /**
     * @var mixed
     */
    private $stage = null;

    /**
     * @var Promised
     */
    private $sleep = null;

    /**
     * @var int
     */
    private $signal = null;

    /**
     * @var mixed
     */
    private $result = null;

    /**
     * Job constructor.
     * @param Generator $co
     * @param Context $ctx
     * @param int $priority
     */
    public function __construct(
        Generator $co,
        Context $ctx = null,
        int $priority = Priority::MEDIUM
    ) {
        Stats::created();

        $this->p = $priority;
        $this->id = IDGen::next();
        $this->co = $co;
        $this->ctx = $ctx ?? new Context();

        $this->chain = new SplStack();

        ($this->ender = Promise::deferred())->catch(function (...$args) {
            $this->killed(...$args);
        });

        $this->run();
    }

    /**
     */
    public function __destruct()
    {
        Stats::finished();
    }

    /**
     * @return int
     */
    public function id() : int
    {
        return $this->id;
    }

    /**
     * @return Context
     */
    public function ctx() : Context
    {
        return $this->ctx;
    }

    /**
     * @return int
     */
    public function priority() : int
    {
        return $this->p;
    }

    /**
     * @return int
     */
    public function exec() : int
    {
        // check if signal is presented
        $this->signal && $this->stage = $this->result;

        // co interaction
        try {
            if ($this->started) {
                if ($this->stage instanceof Throwable) {
                    $this->stage = $this->co->throw($this->stage);
                } else {
                    $this->stage = $this->co->send($this->stage);
                }
            } else {
                $this->started = true;
                $this->stage = $this->co->current();
            }
        } catch (Throwable $e) {
            $this->stage = $e;
        }

        // state switcher
        if ($this->stage instanceof Generator) {
            // jump next co
            $this->chain->push($this->co);
            $this->co = $this->stage;
            $this->started = false;
            // job switch
            return Signal::ROLL;
        }

        // valid checker
        if ($this->co->valid()) {
            // sleeping ?
            if ($this->stage instanceof Promised) {
                // wait promise
                return $this->sleep($this->stage);
            } elseif ($this->stage instanceof Syscall) {
                // exec syscall
                $this->stage = $this->stage->exec($this);
            }
            // keep running
            return Signal::KEEP;
        }

        // co rolling for some features such as "defer"
        $this->rolling();

        // check if co/chain is finished
        if ($this->chain->isEmpty()) {
            // result detector
            $this->result = $this->stage instanceof Throwable ? $this->stage : $this->co->getReturn();
            // job done
            return $this->signal ?? Signal::FIN;
        }

        // trying to get returned value if no exception happens
        $this->stage instanceof Throwable || $this->stage = $this->co->getReturn();

        // jump prev co
        $this->co = $this->chain->pop();

        // sleeping ?
        if ($this->stage instanceof Promised) {
            // wait promise
            return $this->sleep($this->stage);
        }

        // job switch
        return Signal::ROLL;
    }

    /**
     * @param Promised $await
     * @return int
     */
    private function sleep(Promised $await) : int
    {
        $this->sleep = $await;

        $await->then(function ($r = null) {
            $this->wakeup($r);
        }, function (Throwable $e = null) {
            $this->wakeup($e ?? new RejectedException());
        });

        return Signal::SLEEP;
    }

    /**
     * @param $result
     */
    public function wakeup($result) : void
    {
        $this->sleep = null;
        $this->stage = $result;
        $this->run();
    }

    /**
     * @return mixed
     */
    public function stage()
    {
        return $this->stage;
    }

    /**
     * @return mixed
     */
    public function result()
    {
        return $this->result;
    }

    /**
     * @return bool
     */
    public function failure() : bool
    {
        return $this->result instanceof Throwable;
    }

    /**
     * @param Throwable $e
     */
    public function killed(Throwable $e = null) : void
    {
        $this->signal = Signal::KILL;
        $this->result = $e = $e ?? new InterruptException();
        $this->sleep instanceof Promised ? $this->sleep->throw($e) : $this->run();
    }

    /**
     * @return void
     */
    public function rolling() : void
    {
        if (isset($this->rollers[$idx = $this->chain->count()])) {
            $this->rollers[$idx]->resolve($this->stage());
            unset($this->rollers[$idx]);
        }
    }

    /**
     * @return Promised
     */
    public function roll() : Promised
    {
        return $this->rollers[$idx = $this->chain->count()] ?? $this->rollers[$idx] = Promise::deferred();
    }

    /**
     * @return Promised
     */
    public function end() : Promised
    {
        return $this->ender;
    }

    /**
     * @return void
     */
    private function run() : void
    {
        Worker::process($this);
    }
}
