<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use Bow\Security\Crypto;
use RuntimeException;
use Throwable;

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
     * Define the work time out
     *
     * @var integer
     */
    protected int $timeout = 120;

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
    protected int $sleep = 0;

    /**
     * Whether to suppress logging (useful for testing)
     *
     * @var bool
     */
    protected static bool $suppressLogging = false;

    /**
     * Cached queue payload protection mode ('encrypt' | 'sign'), resolved once
     * from config on first use. Null until resolved.
     *
     * @var ?string
     */
    private ?string $payload_protection = null;

    /**
     * Enable or disable logging suppression
     *
     * @param bool $suppress
     * @return void
     */
    public static function suppressLogging(bool $suppress = true): void
    {
        static::$suppressLogging = $suppress;
    }

    /**
     * Make adapter configuration
     *
     * @param  array $config
     * @return QueueAdapter
     */
    abstract public function configure(array $config): QueueAdapter;

    /**
     * Push new task
     *
     * @param QueueTask $task
     * @return bool
     */
    abstract public function push(QueueTask $task): bool;

    /**
     * Create task serialization
     *
     * @param  QueueTask $task
     * @return string
     */
    public function serializeProducer(QueueTask $task): string
    {
        // Authenticate the payload so a worker will only ever unserialize bytes
        // this application produced. Without this, anyone able to write to the
        // queue backend could deliver a crafted serialized object and trigger PHP
        // object injection (RCE via a POP chain) when the worker deserializes it.
        //
        // 'encrypt' (default) also keeps the payload confidential in the broker;
        // 'sign' leaves it readable for debugging while staying tamper-proof.
        $serialized = serialize($task);

        return $this->payloadProtection() === 'sign'
            ? Crypto::sign($serialized)
            : Crypto::encrypt($serialized);
    }

    /**
     * Resolve the configured queue payload protection mode.
     *
     * 'encrypt' (confidential + tamper-proof) is the secure default; only an
     * explicit config('queue.payload_protection') === 'sign' opts into the
     * readable-but-signed format. Falls back to 'encrypt' when config is not
     * booted, so the secure behaviour holds in every context.
     *
     * @return string
     */
    private function payloadProtection(): string
    {
        if ($this->payload_protection === null) {
            try {
                $mode = config('queue.payload_protection');
            } catch (Throwable) {
                $mode = null;
            }

            $this->payload_protection = $mode === 'sign' ? 'sign' : 'encrypt';
        }

        return $this->payload_protection;
    }

    /**
     * Create task unserialize
     *
     * @param  string $task
     * @return QueueTask
     */
    public function unserializeProducer(string $task): QueueTask
    {
        // Verify integrity BEFORE unserialize(). Both schemes fail closed
        // (return false) on a tampered, forged or wrong-key payload, so crafted
        // bytes never reach unserialize(). We accept either the signed or the
        // encrypted format regardless of the configured mode, so flipping
        // queue.payload_protection during a rollout never drops in-flight jobs.
        $plain = Crypto::verify($task);

        if ($plain === false) {
            $plain = Crypto::decrypt($task);
        }

        if ($plain === false) {
            throw new RuntimeException(
                'Queue payload failed integrity verification and was rejected.'
            );
        }

        $producer = unserialize($plain);

        // The payload is authentic but names a class this process cannot load:
        // the task was renamed or removed, the worker runs an older revision than
        // the producer, or another application shares the broker. PHP hands back a
        // __PHP_Incomplete_Class, which the QueueTask return type would surface as
        // an opaque TypeError. Name the class instead so the poison payload the
        // adapters record points straight at the missing task.
        if (!$producer instanceof QueueTask) {
            throw new RuntimeException(sprintf(
                'Queue payload does not hold a %s, got %s.',
                QueueTask::class,
                $this->describeProducer($producer)
            ));
        }

        return $producer;
    }

    /**
     * Describe what came out of the payload for the rejection message
     *
     * A __PHP_Incomplete_Class keeps the original class name in a magic property,
     * which get_class() does not expose, so read it out to report the task the
     * worker is missing rather than the placeholder type.
     *
     * @param  mixed $producer
     * @return string
     */
    private function describeProducer(mixed $producer): string
    {
        if (!is_object($producer)) {
            return get_debug_type($producer);
        }

        if (!$producer instanceof \__PHP_Incomplete_Class) {
            return $producer::class;
        }

        $name = ((array) $producer)['__PHP_Incomplete_Class_Name'] ?? 'unknown';

        return sprintf('the unloadable class %s', $name);
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
     * Set worker timeout
     *
     * @param integer $timeout
     * @return void
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Update the processing timeout
     *
     * @param int  $timeout
     * @return void
     */
    public function updateProcessingTimeout(?int $timeout = null): void
    {
        $this->processing_timeout = time() + ($timeout ?? $this->timeout);
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
        [$this->processing_timeout, $tasks_processed] = [time() + $timeout, 0];

        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            try {
                $this->setTimeout($timeout);
                $this->updateProcessingTimeout();
                $this->run($this->queue);
            } finally {
                $this->sleep($this->sleep);
                $tasks_processed++;
            }

            if ($this->timeoutReached($timeout)) {
                // $this->kill(static::EXIT_ERROR);
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
     * Set task tries
     *
     * @param  int $tries
     * @return void
     */
    public function setTries(int $tries): void
    {
        $this->tries = $tries;
    }

    /**
     * Get task tries
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
     * Set the queue name
     *
     * @param string $queue
     */
    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * Get the queue size
     *
     * @param  ?string $queue
     * @return int
     */
    public function size(?string $queue = null): int
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
     * Log an error
     *
     * @param  Throwable $exception
     * @return void
     */
    protected function logError(Throwable $exception): void
    {
        error_log($exception->getMessage());

        try {
            logger()->error($exception->getMessage(), $exception->getTrace());
        } catch (Throwable $loggerException) {
            // Logger not available, already logged to error_log
        }
    }

    /**
     * Generate the task id
     *
     * @return string
     */
    final protected function generateId(): string
    {
        return md5(uniqid((string) time(), true) . bin2hex(random_bytes(10)) . str_uuid() . microtime(true));
    }

    /**
     * Store the failed payload for later inspection
     *
     * Recording is best effort: the cache is not guaranteed to be configured in
     * a worker process, and a throw here would escape the failure handler and
     * kill the worker before the message is settled, making it redeliver.
     *
     * @param  string $key
     * @param  mixed $payload
     * @return void
     */
    protected function recordFailedPayload(string $key, mixed $payload): void
    {
        try {
            cache($key, $payload);
        } catch (Throwable $exception) {
            $this->logError($exception);
        }
    }

    /**
     * Log processing task
     *
     * @param QueueTask $task
     * @return void
     */
    protected function logProcessingTask(QueueTask $task): void
    {
        if (static::$suppressLogging) {
            return;
        }

        error_log('Processing task: ' . get_class($task) . ' with ID: ' . $task->getId());
    }

    /**
     * Log processed task
     *
     * @param QueueTask $task
     * @return void
     */
    protected function logProcessedTask(QueueTask $task): void
    {
        if (static::$suppressLogging) {
            return;
        }
        error_log('Processed task: ' . get_class($task) . ' with ID: ' . $task->getId());
    }

    /**
     * Log failed task
     *
     * The task is nullable because a failure can occur before the task is
     * resolved, typically when the payload cannot be unserialized.
     *
     * @param QueueTask|null $task
     * @param \Throwable $e
     * @return void
     */
    protected function logFailedTask(?QueueTask $task, \Throwable $e): void
    {
        if (static::$suppressLogging) {
            return;
        }

        $description = is_null($task)
            ? 'unresolved task'
            : get_class($task) . ' with ID: ' . $task->getId();

        error_log('Task failed: ' . $description . ', ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
