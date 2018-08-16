<?php
/**
 * Syscall
 * User: moyo
 * Date: 16/08/2017
 * Time: 2:52 PM
 */

namespace Carno\Coroutine;

use Throwable;

class Syscall
{
    /**
     * @var callable
     */
    private $program = null;

    /**
     * Syscall constructor.
     * @param callable $program
     */
    public function __construct(callable $program)
    {
        $this->program = $program;
    }

    /**
     * @param Job $job
     * @return mixed
     */
    public function exec(Job $job)
    {
        try {
            return ($this->program)($job);
        } catch (Throwable $e) {
            return $e;
        }
    }
}
