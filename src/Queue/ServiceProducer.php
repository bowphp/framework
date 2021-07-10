<?php

namespace Bow\Queue;

abstract class ServiceProducer
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
     * Process the producer
     *
     * @return mixed
     */
    abstract public function process();
}
