<?php

declare(strict_types=1);

namespace Bow\Scheduler;

use DateTime;
use Closure;
use Throwable;
use Bow\Queue\Connection;
use Bow\Queue\QueueTask;
use Bow\Scheduler\Exceptions\SchedulerException;

class ScheduledEvent
{
    /**
     * Event types
     */
    public const TYPE_COMMAND = 'command';
    public const TYPE_TASK = 'task';
    public const TYPE_EXEC = 'exec';
    public const TYPE_CALL = 'call';

    /**
     * The event type
     *
     * @var string
     */
    protected string $type;

    /**
     * The event target (command name, task class, shell command, or closure)
     *
     * @var mixed
     */
    protected mixed $target;

    /**
     * The event parameters
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * The schedule instance
     *
     * @var Schedule
     */
    protected Schedule $schedule;

    /**
     * The unique mutex key for preventing overlaps
     *
     * @var ?string
     */
    protected ?string $mutexName = null;

    /**
     * The last run time
     *
     * @var ?DateTime
     */
    protected ?DateTime $lastRunAt = null;

    /**
     * Whether the event is running
     *
     * @var bool
     */
    protected bool $running = false;

    /**
     * The output of the last execution
     *
     * @var ?string
     */
    protected ?string $output = null;

    /**
     * The exit code of the last execution
     *
     * @var ?int
     */
    protected ?int $exitCode = null;

    /**
     * Before callback
     *
     * @var ?callable
     */
    protected $beforeCallback = null;

    /**
     * After callback
     *
     * @var ?callable
     */
    protected $afterCallback = null;

    /**
     * Failed callback
     *
     * @var ?callable
     */
    protected $failedCallback = null;

    /**
     * The queue connection to use for task execution
     *
     * @var ?string
     */
    protected ?string $connection = null;

    /**
     * Create a new scheduled event
     *
     * @param  string $type
     * @param  mixed  $target
     * @param  array  $parameters
     * @return void
     */
    public function __construct(string $type, mixed $target, array $parameters = [])
    {
        $this->type = $type;
        $this->target = $target;
        $this->parameters = $parameters;
        $this->schedule = new Schedule();
        $this->schedule->setEvent($this);
    }

    /**
     * Get the schedule instance
     *
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    /**
     * Get the event type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the event target
     *
     * @return mixed
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }

    /**
     * Get the mutex name for this event
     *
     * @return string
     */
    public function getMutexName(): string
    {
        if ($this->mutexName) {
            return $this->mutexName;
        }

        $identifier = match ($this->type) {
            self::TYPE_COMMAND => $this->target,
            self::TYPE_TASK => is_string($this->target) ? $this->target : get_class($this->target),
            self::TYPE_EXEC => $this->target,
            self::TYPE_CALL => spl_object_hash((object) $this->target),
            default => uniqid(),
        };

        return 'scheduler:' . md5($identifier);
    }

    /**
     * Set a custom mutex name
     *
     * @param  string $name
     * @return $this
     */
    public function setMutexName(string $name): static
    {
        $this->mutexName = $name;

        return $this;
    }

    /**
     * Check if the event is due to run
     *
     * @param  ?DateTime $currentTime
     * @return bool
     */
    public function isDue(?DateTime $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTime();

        return $this->schedule->isDue($currentTime) && $this->schedule->filtersPass();
    }

    /**
     * Run the scheduled event
     *
     * @return void
     * @throws SchedulerException
     */
    public function run(): void
    {
        if ($this->running) {
            throw new SchedulerException('Event is already running');
        }

        try {
            $this->running = true;
            $this->lastRunAt = new DateTime();
            $this->execute();
        } finally {
            $this->running = false;
        }
    }

    /**
     * Execute the event based on its type
     *
     * @return void
     * @throws SchedulerException
     */
    protected function execute(): void
    {
        match ($this->type) {
            self::TYPE_COMMAND => $this->executeCommand(),
            self::TYPE_TASK => $this->executeTask(),
            self::TYPE_EXEC => $this->executeExec(),
            self::TYPE_CALL => $this->executeCall(),
            default => throw new SchedulerException("Unknown event type: {$this->type}"),
        };
    }

    /**
     * Execute a Bow console command
     *
     * @return void
     */
    protected function executeCommand(): void
    {
        $command = $this->buildBowCommand();
        $this->runShellCommand($command);
    }

    /**
     * Execute a QueueTask
     *
     * @return void
     * @throws SchedulerException
     */
    protected function executeTask(): void
    {
        $task = $this->target;

        // If it's a class name, instantiate it
        if (is_string($task)) {
            if (!class_exists($task)) {
                throw new SchedulerException("Task class [{$task}] does not exist.");
            }
            $task = new $task(...$this->parameters);
        }

        if (!$task instanceof QueueTask) {
            throw new SchedulerException(
                "Task must be an instance of " . QueueTask::class
            );
        }

        // Always push to queue
        $this->pushToQueue($task);
    }

    /**
     * Push the task to a queue connection
     *
     * @param  QueueTask $task
     * @return void
     */
    protected function pushToQueue(QueueTask $task): void
    {
        /** @var Connection $queue */
        $queue = app('queue');

        if ($this->connection !== null) {
            $queue->setConnection($this->connection);
        }

        $queue->push($task);
    }

    /**
     * Set the queue connection to use for task execution
     *
     * @param  string $connection
     * @return $this
     */
    public function onConnection(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the queue connection
     *
     * @return ?string
     */
    public function getConnection(): ?string
    {
        return $this->connection;
    }

    /**
     * Execute a shell command
     *
     * @return void
     */
    protected function executeExec(): void
    {
        $command = $this->target;

        if (!empty($this->parameters)) {
            $params = array_map('escapeshellarg', $this->parameters);
            $command .= ' ' . implode(' ', $params);
        }

        $this->runShellCommand($command);
    }

    /**
     * Execute a closure/callback
     *
     * @return void
     */
    protected function executeCall(): void
    {
        call_user_func_array($this->target, $this->parameters);
    }

    /**
     * Build a Bow console command
     *
     * @return string
     */
    protected function buildBowCommand(): string
    {
        $phpBinary = PHP_BINARY ?: 'php';
        $bowPath = $this->getBowPath();
        $command = $this->target;

        $params = [];
        foreach ($this->parameters as $key => $value) {
            if (is_int($key)) {
                $params[] = escapeshellarg((string) $value);
            } elseif (is_bool($value)) {
                if ($value) {
                    $params[] = $key;
                }
            } else {
                $params[] = "{$key}=" . escapeshellarg((string) $value);
            }
        }

        $paramString = !empty($params) ? ' ' . implode(' ', $params) : '';

        return "{$phpBinary} {$bowPath} {$command}{$paramString}";
    }

    /**
     * Run a shell command
     *
     * @param  string $command
     * @return void
     * @throws SchedulerException
     */
    protected function runShellCommand(string $command): void
    {
        if ($this->schedule->shouldRunInBackground()) {
            $this->runInBackground($command);
            return;
        }

        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        $this->output = implode("\n", $output);
        $this->exitCode = $exitCode;

        if ($exitCode !== 0) {
            throw new SchedulerException(
                "Command [{$command}] failed with exit code {$exitCode}: {$this->output}"
            );
        }
    }

    /**
     * Run command in background
     *
     * @param  string $command
     * @return void
     */
    protected function runInBackground(string $command): void
    {
        // For Unix-like systems, run in background with nohup
        if (PHP_OS_FAMILY !== 'Windows') {
            $command = "nohup {$command} > /dev/null 2>&1 &";
        } else {
            $command = "start /B {$command} > NUL 2>&1";
        }

        exec($command);
        $this->exitCode = 0;
        $this->output = 'Running in background';
    }

    /**
     * Get the path to the bow executable
     *
     * @return string
     */
    protected function getBowPath(): string
    {
        $possiblePaths = [
            getcwd() . '/bow',
            dirname(getcwd()) . '/bow',
            realpath(__DIR__ . '/../../../../bow'),
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return 'bow';
    }

    /**
     * Register a before callback
     *
     * @param  callable $callback
     * @return $this
     */
    public function before(callable $callback): static
    {
        $this->beforeCallback = $callback;

        return $this;
    }

    /**
     * Register an after callback
     *
     * @param  callable $callback
     * @return $this
     */
    public function after(callable $callback): static
    {
        $this->afterCallback = $callback;

        return $this;
    }

    /**
     * Register a failed callback
     *
     * @param  callable $callback
     * @return $this
     */
    public function onFailure(callable $callback): static
    {
        $this->failedCallback = $callback;

        return $this;
    }

    /**
     * Execute the before callback
     *
     * @return void
     */
    public function runBeforeCallback(): void
    {
        if ($this->beforeCallback) {
            call_user_func($this->beforeCallback, $this);
        }
    }

    /**
     * Execute the after callback
     *
     * @return void
     */
    public function runAfterCallback(): void
    {
        if ($this->afterCallback) {
            call_user_func($this->afterCallback, $this);
        }
    }

    /**
     * Execute the failed callback
     *
     * @param  Throwable $exception
     * @return void
     */
    public function runFailedCallback(Throwable $exception): void
    {
        if ($this->failedCallback) {
            call_user_func($this->failedCallback, $this, $exception);
        }
    }

    /**
     * Get the last run time
     *
     * @return ?DateTime
     */
    public function getLastRunAt(): ?DateTime
    {
        return $this->lastRunAt;
    }

    /**
     * Check if the event is currently running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Get the cron expression for this event
     *
     * @return string
     */
    public function getCronExpression(): string
    {
        return $this->schedule->getExpression();
    }

    /**
     * Get the output from the last execution
     *
     * @return ?string
     */
    public function getOutput(): ?string
    {
        return $this->output;
    }

    /**
     * Get the exit code from the last execution
     *
     * @return ?int
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * Get the event description
     *
     * @return string
     */
    public function getDescription(): string
    {
        $description = $this->schedule->getDescription();

        if ($description) {
            return $description;
        }

        return match ($this->type) {
            self::TYPE_COMMAND => "php bow {$this->target}",
            self::TYPE_TASK => is_string($this->target) ? $this->target : get_class($this->target),
            self::TYPE_EXEC => $this->target,
            self::TYPE_CALL => 'Closure',
            default => 'Unknown',
        };
    }
}
