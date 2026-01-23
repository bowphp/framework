<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;

abstract class QueueAdapter
{
    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 1;
    const EXIT_MEMORY_LIMIT = 12;

    /**
     * Define the start time
     *
     * @var float
     */
    protected float $start_time;

    /**
     * Define the processing timeout
     *
     * @var float
     */
    protected float $processing_timeout;

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
     * @param  array $config
     * @return QueueAdapter
     */
    abstract public function configure(array $config): QueueAdapter;

    /**
     * Push new job
     *
     * @param QueueTask $job
     * @return bool
     */
    abstract public function push(QueueTask $job): bool;

    /**
     * Create job serialization
     *
     * @param  QueueTask $job
     * @return string
     */
    public function serializeProducer(QueueTask $job): string
    {
        return serialize($job);
    }

    /**
     * Create job unserialize
     *
     * @param  string $job
     * @return QueueTask
     */
    public function unserializeProducer(string $job): QueueTask
    {
        return unserialize($job);
    }

    /**
     * Sleep the process
     *
     * @param  int $seconds
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
     * Update the processing timeout
     *
     * @return void
     */
    public function updateProcessingTimeout(): void
    {
        $this->processing_timeout = time();
    }

    /**
     * Launch the worker
     *
     * @param  integer $timeout
     * @param  integer $memory
     * @return void
     */
    final public function work(int $timeout, int $memory): void
    {
        [$this->processing_timeout, $jobs_processed] = [time(), 0];

        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            try {
                $this->updateProcessingTimeout();
                $this->run();
            } finally {
                $this->sleep($this->sleep);
                $jobs_processed++;
            }

            if ($this->timeoutReached($timeout)) {
                $this->kill(static::EXIT_ERROR);
            } elseif ($this->memoryExceeded($memory)) {
                $this->kill(static::EXIT_MEMORY_LIMIT);
            }
        }
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGQUIT, fn() => error_log("bow worker exiting..."));
        pcntl_signal(SIGTERM, fn() => error_log("bow worker exit..."));
        pcntl_signal(SIGUSR2, fn() => error_log("bow worker restarting..."));
        pcntl_signal(SIGCONT, fn() => error_log("bow worker continue..."));
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
     * Determine if the timeout is reached
     *
     * @param  int $timeout
     * @return boolean
     */
    protected function timeoutReached(int $timeout): bool
    {
        return (time() - $this->processing_timeout) >= $timeout;
    }

    /**
     * Kill the process.
     *
     * @param  int $status
     * @return void
     */
    public function kill(int $status = 0): void
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Determine if the memory is exceeded
     *
     * @param  int $memory_timit
     * @return boolean
     */
    private function memoryExceeded(int $memory_timit): bool
    {
        return (memory_get_usage() / 1024 / 1024) >= $memory_timit;
    }

    /**
     * Set job tries
     *
     * @param  int $tries
     * @return void
     */
    public function setTries(int $tries): void
    {
        $this->tries = $tries;
    }

    /**
     * Get job tries
     *
     * @return int
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * Set sleep time
     *
     * @param  int $sleep
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
     * Watch the queue name
     *
     * @param string $queue
     */
    public function setQueue(string $queue): void
    {
        //
    }

    /**
     * Get the queue size
     *
     * @param  string $queue
     * @return int
     */
    public function size(string $queue): int
    {
        return 0;
    }

    /**
     * Flush the queue
     *
     * @param  ?string $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        //
    }

    /**
     * Generate the job id
     *
     * @return string
     */
    final protected function generateId(): string
    {
        return md5(uniqid((string) time(), true) . bin2hex(random_bytes(10)) . str_uuid() . microtime(true));
    }
}
