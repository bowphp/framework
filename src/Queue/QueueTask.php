<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Support\Serializes;
use Throwable;

abstract class QueueTask
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
    protected int $delay = 0;

    /**
     * Define the time of retry
     *
     * @var int
     */
    protected int $retry = 30;

    /**
     * Define the priority
     *
     * @var int
     */
    protected int $priority = 1;

    /**
     * Determine if the task can be deleted
     *
     * @var bool
     */
    protected bool $delete = false;

    /**
     * Define the task id
     *
     * @return integer
     */
    protected ?string $id = null;

    /**
     * Define the task attempts
     *
     * @var int
     */
    protected int $attempts = 2;

    /**
     * Set the task ID
     *
     * @param  string $id
     * @return void
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * Get the task priority
     *
     * @return int
     */
    final public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the task id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the task attempts
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set the task attempts
     *
     * @param  int $attempts
     * @return void
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * Get the task retry
     *
     * @return int
     */
    final public function getRetry(): int
    {
        return $this->retry;
    }

    /**
     * Set the task retry
     *
     * @param  int $retry
     * @return void
     */
    final public function setRetry(int $retry): void
    {
        $this->retry = $retry;
    }

    /**
     * Get the task queue
     *
     * @return string
     */
    final public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Set the task queue
     *
     * @param  string $queue
     * @return void
     */
    final public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * Get the task delay
     *
     * @return int
     */
    final public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Set the task delay
     *
     * @param int $delay
     */
    final public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * Delete the task from queue.
     *
     * @return void
     */
    public function deleteTask(): void
    {
        $this->delete = true;
    }

    /**
     * Delete the task from queue.
     *
     * @return bool
     */
    public function taskShouldBeDelete(): bool
    {
        return $this->delete;
    }

    /**
     * Delete the task from queue.
     *
     * @return bool
     */
    public function jobShouldBeDelete()
    {
        return $this->delete;
    }

    /**
     * Get the task error
     *
     * @param  Throwable $e
     * @return void
     */
    public function onException(Throwable $e)
    {
        //
    }

    /**
     * Process the task
     *
     * @return void
     */
    abstract public function process(): void;
}
