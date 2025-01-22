<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Support\Serializes;
use Throwable;

abstract class ProducerService
{
    use Serializes;

    /**
     * Define the queue
     *
     * @var string
     */
    protected string $queue = "default";

    /**
     * Define the delay
     *
     * @var int
     */
    protected int $delay = 30;

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
     * Determine if the job can be deleted
     *
     * @var bool
     */
    protected bool $delete = false;

    /**
     * Define the job id
     *
     * @return integer
     */
    protected ?string $id = null;

    /**
     * Define the job attempts
     *
     * @var int
     */
    protected int $attempts = 2;

    /**
     * ProducerService constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->id = str_uuid();
    }

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
     * Get the producer id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the producer attempts
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set the producer attempts
     *
     * @param int $attempts
     * @return void
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
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
     * Set the producer retry
     *
     * @param int $retry
     * @return void
     */
    final public function setRetry(int $retry): void
    {
        $this->retry = $retry;
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
     * Set the producer queue
     *
     * @param string $queue
     * @return void
     */
    final public function setQueue(string $queue): void
    {
        $this->queue = $queue;
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
     * Set the producer delay
     *
     * @param int $delay
     */
    final public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * Delete the job from queue.
     *
     * @return void
     */
    public function deleteJob(): void
    {
        $this->delete = true;
    }

    /**
     * Delete the job from queue.
     *
     * @return bool
     */
    public function jobShouldBeDelete(): bool
    {
        return $this->delete;
    }

    /**
     * Get the job error
     *
     * @param Throwable $e
     * @return void
     */
    public function onException(Throwable $e)
    {
        //
    }

    /**
     * Process the producer
     *
     * @return void
     */
    abstract public function process(): void;
}
