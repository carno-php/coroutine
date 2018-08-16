<?php
/**
 * Priority queue for jobs
 * User: moyo
 * Date: 06/08/2017
 * Time: 2:47 PM
 */

namespace Carno\Coroutine\Scheduled;

use Carno\Coroutine\Job;
use SplPriorityQueue;

class PQJobs extends SplPriorityQueue
{
    /**
     * PQJobs constructor.
     */
    public function __construct()
    {
        $this->setExtractFlags(self::EXTR_DATA);
    }

    /**
     * @param mixed $priority1
     * @param mixed $priority2
     * @return int
     */
    public function compare($priority1, $priority2) : int
    {
        return $priority2 <=> $priority1;
    }

    /**
     * @param Job $job
     */
    public function enqueue(Job $job) : void
    {
        $this->insert($job, $job->priority());
    }

    /**
     * @return Job
     */
    public function dequeue() : Job
    {
        return $this->extract();
    }
}
