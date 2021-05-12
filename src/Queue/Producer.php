<?php

namespace Bow\Queue;

use Pheanstalk\Pheanstalk;

abstract class Producer
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
    protected $priority = Pheanstalk::DEFAULT_PRIORITY;

    /**
     * Process the producer
     *
     * @return mixed
     */
    abstract public function process();
}
