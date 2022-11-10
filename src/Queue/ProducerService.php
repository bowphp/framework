<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Packages\Traits\SerializationTrait;

abstract class ProducerService
{
    use SerializationTrait;

    /**
     * Define the delay
     *
     * @var int
     */
    protected int $delay = 30;

    /**
     * Define the queue
     *
     * @var string
     */
    protected string $queue = "default";

    /**
     * Define the time of retry
     *
     * @var int
     */
    protected int $retry = 60;

    /**
     * Define the priority
     *
     * @var int
     */
    protected int $priority = 1;

    /**
     * Get the producer priority
     *
     * @return int
     */
    final public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the producer retry
     *
     * @return int
     */
    final public function getRetry(): int
    {
        return $this->retry;
    }

    /**
     * Get the producer queue
     *
     * @return string
     */
    final public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Get the producer delay
     *
     * @return int
     */
    final public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Process the producer
     *
     * @return mixed
     */
    abstract public function process(): void;
}
