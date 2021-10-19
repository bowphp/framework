<?php

namespace Bow\Queue;

abstract class ProducerService
{
    /**
     * Define the delay
     *
     * @var integer
     */
    protected $delay = 30;

    /**
     * Define the queue
     *
     * @var string
     */
    protected $queue = "default";

    /**
     * Define the time of retry
     *
     * @var integer
     */
    protected $retry = 60;

    /**
     * Define the priority
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * Get the producer priority
     * 
     * @return int
     */
    final public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Get the producer retry
     * 
     * @return int
     */
    final public function getRetry()
    {
        return $this->retry;
    }

    /**
     * Get the producer queue
     * 
     * @return int
     */
    final public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the producer delay
     * 
     * @return int
     */
    final public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Process the producer
     *
     * @return mixed
     */
    abstract public function process();
}
