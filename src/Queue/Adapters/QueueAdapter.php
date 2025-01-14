<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\ProducerService;

abstract class QueueAdapter
{
    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 1;
    const EXIT_MEMORY_LIMIT = 12;

    /**
     * Define the start time
     *
     * @var int
     */
    protected float $start_time;

    /**
     * Determine the default watch name
     *
     * @var string
     */
    protected string $queue = "default";

    /**
     * The number of working attempts
     *
     * @var int
     */
    protected int $tries = 3;

    /**
     * Define the sleep time
     *
     * @var int
     */
    protected int $sleep = 5;

    /**
     * Make adapter configuration
     *
     * @param array $config
     * @return QueueAdapter
     */
    abstract public function configure(array $config): QueueAdapter;

    /**
     * Push new producer
     *
     * @param ProducerService $producer
     */
    abstract public function push(ProducerService $producer): void;

    /**
     * Create producer serialization
     *
     * @param ProducerService $producer
     * @return string
     */
    public function serializeProducer(
        ProducerService $producer
    ): string {
        return serialize($producer);
    }

    /**
     * Create producer unserialize
     *
     * @param string $producer
     * @return ProducerService
     */
    public function unserializeProducer(
        string $producer
    ): ProducerService {
        return unserialize($producer);
    }

    /**
     * Sleep the process
     *
     * @param int $seconds
     * @return void
     */
    public function sleep(int $seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Laund the worker
     *
     * @param integer $timeout
     * @param integer $memory
     * @return void
     */
    final public function work(int $timeout, int $memory): void
    {
        [$this->start_time, $jobs_processed] = [hrtime(true) / 1e9, 0];

        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            $this->run();
            $jobs_processed++;

            if ($this->timeoutReached($timeout)) {
                $this->kill(static::EXIT_ERROR);
            } elseif ($this->memoryExceeded($memory)) {
                $this->kill(static::EXIT_MEMORY_LIMIT);
            }
        }
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return never
     */
    public function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Determine if the timeout is reached
     *
     * @param int $timeout
     * @return boolean
     */
    protected function timeoutReached(int $timeout): bool
    {
        return (time() - $this->start_time) >= $timeout;
    }

    /**
     * Determine if the memory is exceeded
     *
     * @param int $memory_timit
     * @return boolean
     */
    private function memoryExceeded(int $memory_timit): bool
    {
        return (memory_get_usage() / 1024 / 1024) >= $memory_timit;
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn () => error_log("bow worker exiting..."));
        pcntl_signal(SIGTERM, fn () => error_log("bow worker exit..."));
        pcntl_signal(SIGUSR2, fn () => error_log("bow worker restarting..."));
        pcntl_signal(SIGCONT, fn () => error_log("bow worker continue..."));
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl');
    }

    /**
     * Set job tries
     *
     * @param int $tries
     * @return void
     */
    public function setTries(int $tries): void
    {
        $this->tries = $tries;
    }

    /**
     * Set sleep time
     *
     * @param int $sleep
     * @return void
     */
    public function setSleep(int $sleep): void
    {
        $this->sleep = $sleep;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  ?string $queue
     * @return string
     */
    public function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->queue;
    }

    /**
     * Generate the job id
     *
     * @return string
     */
    public function generateId(): string
    {
        return sha1(uniqid(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), true));
    }

    /**
     * Get the queue size
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue): int
    {
        return 0;
    }

    /**
     * Start the worker server
     *
     * @param ?string $queue
     */
    public function run(?string $queue = null): void
    {
        //
    }

    /**
     * Flush the queue
     *
     * @param ?string $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        //
    }

    /**
     * Watch the queue name
     *
     * @param string $queue
     */
    public function setQueue(string $queue): void
    {
        //
    }
}
