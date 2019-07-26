<?php
/**
 * Jobs queue
 * User: moyo
 * Date: 01/08/2017
 * Time: 4:06 PM
 */

namespace Carno\Coroutine;

use Carno\Coroutine\Job\Signal;
use Carno\Coroutine\Scheduled\PQJobs;

class Queue
{
    /**
     * @var PQJobs
     */
    private $jobs = null;

    /**
     * Queue constructor.
     */
    public function __construct()
    {
        $this->jobs = new PQJobs();
    }

    /**
     * @param Job $job
     * @return Queue
     */
    public function join(Job $job) : Queue
    {
        $this->jobs->enqueue($job);
        return $this;
    }

    /**
     * run jobs
     */
    public function loop() : void
    {
        while ($this->jobs->valid()) {
            $job = $this->jobs->dequeue();
            RUNNING:
            switch ($job->exec()) {
                case Signal::SLEEP:
                    break;
                case Signal::KEEP:
                    goto RUNNING;
                    break;
                case Signal::ROLL:
                    $this->jobs->enqueue($job);
                    break;
                case Signal::KILL:
                    $job->end()->pended() && $job->end()->throw($job->result());
                    break;
                case Signal::FIN:
                    $job->failure() ? $job->end()->throw($job->result()) : $job->end()->resolve($job->result());
                    break;
            }
        }
    }
}
