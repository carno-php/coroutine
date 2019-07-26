<?php
/**
 * Global worker
 * User: moyo
 * Date: 01/08/2017
 * Time: 2:57 PM
 */

namespace Carno\Coroutine;

use Carno\Coroutine\Worker\Singleton;

class Worker
{
    use Singleton;

    /**
     * @var Queue
     */
    private $scheduler = null;

    /**
     * Worker constructor.
     */
    public function __construct()
    {
        $this->scheduler = new Queue();
    }

    /**
     * @param Job $job
     * @return Worker
     */
    public function accept(Job $job) : Worker
    {
        $this->scheduler->join($job);
        return $this;
    }

    /**
     * @return void
     */
    public function run() : void
    {
        $this->scheduler->loop();
    }
}
