<?php

declare(strict_types=1);

namespace Bow\Scheduler;

use DateTime;
use Throwable;
use Bow\Cache\Cache;
use Bow\Queue\QueueTask;
use Bow\Scheduler\Exceptions\SchedulerException;

/**
 * Class Scheduler
 *
 * Simplified scheduler that provides four main methods:
 * - command(): Run Bow console commands
 * - task(): Run QueueTask classes
 * - exec(): Run bash/shell commands
 * - call(): Run closures/callbacks
 *
 * @package Bow\Scheduler
 */
class Scheduler
{
    /**
     * The Scheduler instance
     *
     * @var ?Scheduler
     */
    private static ?Scheduler $instance = null;

    /**
     * The registered scheduled events
     *
     * @var array<ScheduledEvent>
     */
    private array $events = [];

    /**
     * The cache adapter for mutex locks
     *
     * @var ?Cache
     */
    private ?Cache $cache = null;

    /**
     * Whether logging is enabled
     *
     * @var bool
     */
    private bool $loggingEnabled = true;

    /**
     * The custom logger callback
     *
     * @var ?callable
     */
    private $logger = null;

    /**
     * Scheduler constructor
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        if (static::$instance !== null) {
            throw new \Exception(
                "The Scheduler class is a singleton and already instantiated. " .
                "Please use Scheduler::getInstance() to get the instance."
            );
        }
    }

    /**
     * Get the Scheduler instance
     *
     * @return Scheduler
     */
    public static function getInstance(): Scheduler
    {
        if (static::$instance === null) {
            static::$instance = new Scheduler();
        }

        return static::$instance;
    }

    /**
     * Set the cache adapter for mutex locks
     *
     * @param  Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache): static
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Set a custom logger callback
     *
     * @param  callable $logger
     * @return $this
     */
    public function setLogger(callable $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Enable or disable logging
     *
     * @param  bool $enabled
     * @return $this
     */
    public function enableLogging(bool $enabled = true): static
    {
        $this->loggingEnabled = $enabled;

        return $this;
    }

    /**
     * Schedule a Bow console command
     *
     * @param  string $command    The Bow command (e.g., "migration:migrate", "clear:cache")
     * @param  array  $parameters Optional parameters for the command
     * @return Schedule
     */
    public function command(string $command, array $parameters = []): Schedule
    {
        $event = new ScheduledEvent(
            ScheduledEvent::TYPE_COMMAND,
            $command,
            $parameters
        );

        $this->events[] = $event;

        return $event->getSchedule();
    }

    /**
     * Schedule a QueueTask for execution
     *
     * @param  string|QueueTask $task       The QueueTask class name or instance
     * @param  array            $parameters Parameters for instantiation (if class name provided)
     * @return Schedule
     */
    public function task(string|QueueTask $task, array $parameters = []): Schedule
    {
        $event = new ScheduledEvent(
            ScheduledEvent::TYPE_TASK,
            $task,
            $parameters
        );

        $this->events[] = $event;

        return $event->getSchedule();
    }

    /**
     * Schedule a shell/bash command
     *
     * @param  string $command    The shell command to execute
     * @param  array  $parameters Optional arguments for the command
     * @return Schedule
     */
    public function exec(string $command, array $parameters = []): Schedule
    {
        $event = new ScheduledEvent(
            ScheduledEvent::TYPE_EXEC,
            $command,
            $parameters
        );

        $this->events[] = $event;

        return $event->getSchedule();
    }

    /**
     * Schedule a callback/closure for execution
     *
     * @param  callable $callback   The callback to execute
     * @param  array    $parameters Optional parameters to pass to the callback
     * @return Schedule
     */
    public function call(callable $callback, array $parameters = []): Schedule
    {
        $event = new ScheduledEvent(
            ScheduledEvent::TYPE_CALL,
            $callback,
            $parameters
        );

        $this->events[] = $event;

        return $event->getSchedule();
    }

    /**
     * Get all registered events
     *
     * @return array<ScheduledEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Get all due events
     *
     * @param  ?DateTime $currentTime
     * @return array<ScheduledEvent>
     */
    public function getDueEvents(?DateTime $currentTime = null): array
    {
        $currentTime = $currentTime ?? new DateTime();

        return array_filter(
            $this->events,
            fn(ScheduledEvent $event) => $event->isDue($currentTime)
        );
    }

    /**
     * Run all due events
     *
     * @param  ?DateTime $currentTime
     * @return array
     */
    public function run(?DateTime $currentTime = null): array
    {
        $currentTime = $currentTime ?? new DateTime();
        $dueEvents = $this->getDueEvents($currentTime);
        $results = [];

        foreach ($dueEvents as $event) {
            $results[] = $this->runEvent($event);
        }

        return $results;
    }

    /**
     * Run a single event
     *
     * @param  ScheduledEvent $event
     * @return array
     */
    protected function runEvent(ScheduledEvent $event): array
    {
        $result = [
            'type' => $event->getType(),
            'description' => $event->getDescription(),
            'status' => 'success',
            'started_at' => new DateTime(),
            'finished_at' => null,
            'error' => null,
        ];

        try {
            // Check for overlapping prevention
            if ($event->getSchedule()->shouldPreventOverlapping()) {
                if (!$this->acquireLock($event)) {
                    $result['status'] = 'skipped';
                    $result['error'] = 'Event is already running (overlap prevention)';
                    $this->log("Skipping event [{$event->getDescription()}]: already running");
                    return $result;
                }
            }

            $this->log("Running event: {$event->getDescription()}");

            // Run before callback
            $event->runBeforeCallback();

            // Run the event
            $event->run();

            // Run after callback
            $event->runAfterCallback();

            $result['finished_at'] = new DateTime();
            $this->log("Completed event: {$event->getDescription()}");
        } catch (Throwable $e) {
            $result['status'] = 'failed';
            $result['error'] = $e->getMessage();
            $result['finished_at'] = new DateTime();

            // Run failed callback
            $event->runFailedCallback($e);

            $this->log("Event failed [{$event->getDescription()}]: {$e->getMessage()}");
        } finally {
            // Release lock if using overlap prevention
            if ($event->getSchedule()->shouldPreventOverlapping()) {
                $this->releaseLock($event);
            }
        }

        return $result;
    }

    /**
     * Acquire a lock for overlap prevention
     *
     * @param  ScheduledEvent $event
     * @return bool
     */
    protected function acquireLock(ScheduledEvent $event): bool
    {
        if (!$this->cache) {
            // If no cache is available, we can't prevent overlapping
            return true;
        }

        $mutexName = $event->getMutexName();
        $expiresAt = $event->getSchedule()->getExpiresAt();

        // Check if lock already exists
        if ($this->cache->has($mutexName)) {
            return false;
        }

        // Acquire the lock
        $this->cache->set($mutexName, true, $expiresAt * 60);

        return true;
    }

    /**
     * Release a lock for an event
     *
     * @param  ScheduledEvent $event
     * @return void
     */
    protected function releaseLock(ScheduledEvent $event): void
    {
        if (!$this->cache) {
            return;
        }

        $this->cache->forget($event->getMutexName());
    }

    /**
     * Log a message
     *
     * @param  string $message
     * @return void
     */
    protected function log(string $message): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] SCHEDULER: {$message}";

        if ($this->logger) {
            call_user_func($this->logger, $formattedMessage);
        } else {
            error_log($formattedMessage);
        }
    }

    /**
     * Clear all registered events
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->events = [];

        return $this;
    }

    /**
     * Start the scheduler loop (for CLI daemon mode)
     *
     * @param  int $sleepSeconds
     * @return void
     */
    public function start(int $sleepSeconds = 60): void
    {
        $this->log("Scheduler started");

        while (true) {
            $this->run();

            // Sleep until the next minute
            $this->sleepUntilNextMinute($sleepSeconds);
        }
    }

    /**
     * Sleep until the next minute boundary
     *
     * @param  int $maxSleep
     * @return void
     */
    protected function sleepUntilNextMinute(int $maxSleep = 60): void
    {
        $now = new DateTime();
        $secondsUntilNextMinute = 60 - (int) $now->format('s');

        sleep(min($secondsUntilNextMinute, $maxSleep));
    }

    /**
     * Reset the singleton instance (mainly for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * Magic method for static calls
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::getInstance()->$name(...$arguments);
    }
}
