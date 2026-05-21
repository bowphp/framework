<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use DateTime;
use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Scheduler\Scheduler;
use Bow\Configuration\Loader;

class SchedulerCommand extends AbstractCommand
{
    /**
     * Run the scheduler once (execute all due events)
     *
     * @return void
     */
    public function run(): void
    {
        $scheduler = $this->getScheduler();

        echo Color::green("Running scheduler...\n");

        $results = $scheduler->run();

        if (empty($results)) {
            echo Color::yellow("No scheduled events are due.\n");
            return;
        }

        foreach ($results as $result) {
            $this->displayResult($result);
        }

        echo Color::green("\nScheduler run completed.\n");
    }

    /**
     * Start the scheduler daemon (continuous loop)
     *
     * @return void
     */
    public function work(): void
    {
        $scheduler = $this->getScheduler();

        echo Color::green("Starting scheduler daemon...\n");
        echo Color::yellow("Press Ctrl+C to stop.\n\n");

        // Set up custom logger for console output
        $scheduler->setLogger(function (string $message) {
            echo $message . "\n";
        });

        $scheduler->start();
    }

    /**
     * List all registered scheduled events
     *
     * @return void
     */
    public function list(): void
    {
        $scheduler = $this->getScheduler();
        $events = $scheduler->getEvents();

        if (empty($events)) {
            echo Color::yellow("No scheduled events registered.\n");
            return;
        }

        echo Color::green("Registered Scheduled Events:\n");
        echo str_repeat('-', 100) . "\n";

        printf("%-45s | %-10s | %-15s | %s\n", "Description", "Type", "Expression", "Next Due");
        echo str_repeat('-', 100) . "\n";

        $now = new DateTime();

        foreach ($events as $event) {
            $description = $event->getDescription();
            $type = $event->getType();
            $expression = $event->getCronExpression();
            $isDue = $event->isDue($now);

            // Truncate long descriptions
            if (strlen($description) > 43) {
                $description = substr($description, 0, 40) . '...';
            }

            $dueStatus = $isDue ? Color::green("DUE NOW") : Color::yellow("waiting");

            printf(
                "%-45s | %-10s | %-15s | %s\n",
                $description,
                $type,
                $expression,
                $dueStatus
            );
        }

        echo str_repeat('-', 100) . "\n";
        echo Color::green("Total: " . count($events) . " event(s)\n");
    }

    /**
     * Show the next run time for all events
     *
     * @return void
     */
    public function next(): void
    {
        $scheduler = $this->getScheduler();
        $events = $scheduler->getEvents();

        if (empty($events)) {
            echo Color::yellow("No scheduled events registered.\n");
            return;
        }

        echo Color::green("Next Run Times:\n");
        echo str_repeat('-', 80) . "\n";

        $now = new DateTime();

        foreach ($events as $event) {
            $description = $event->getDescription();
            $isDue = $event->isDue($now);

            $status = $isDue
                ? Color::green("DUE NOW")
                : Color::yellow("waiting");

            echo sprintf(
                "[%-8s] %-50s %s (%s)\n",
                $event->getType(),
                $description,
                $status,
                $event->getCronExpression()
            );
        }

        echo str_repeat('-', 80) . "\n";
    }

    /**
     * Test run a specific event by its index
     *
     * @param  int $index The 0-based index of the event to run
     * @return void
     */
    public function test(int $index = 0): void
    {
        $scheduler = $this->getScheduler();
        $events = $scheduler->getEvents();

        if (empty($events)) {
            echo Color::yellow("No scheduled events registered.\n");
            return;
        }

        if ($index < 0 || $index >= count($events)) {
            echo Color::red("Invalid event index: {$index}\n");
            echo Color::yellow("Use 'php bow schedule:list' to see available events (0-indexed).\n");
            return;
        }

        $event = $events[$index];
        $description = $event->getDescription();

        echo Color::green("Running event: {$description}\n");

        try {
            $startTime = microtime(true);
            $event->run();
            $endTime = microtime(true);

            $duration = round(($endTime - $startTime) * 1000, 2);
            echo Color::green("Event completed successfully in {$duration}ms\n");

            $output = $event->getOutput();
            if ($output) {
                echo Color::yellow("Output:\n{$output}\n");
            }
        } catch (\Throwable $e) {
            echo Color::red("Event failed: " . $e->getMessage() . "\n");
            echo Color::yellow("Stack trace:\n" . $e->getTraceAsString() . "\n");
        }
    }

    /**
     * Get the scheduler instance
     *
     * @return Scheduler
     */
    private function getScheduler(): Scheduler
    {
        $scheduler = Scheduler::getInstance();

        $this->loadSchedulerFile($scheduler);

        return $scheduler;
    }

    /**
     * Load schedules from two sources:
     *
     *   1. The host app's Kernel::schedules() method (always called).
     *   2. A routes/scheduler.php file relative to the app's base directory,
     *      if present. The file is included so any code it runs against
     *      Scheduler::getInstance() registers events.
     *
     * @param  Scheduler $scheduler
     * @return void
     */
    private function loadSchedulerFile(Scheduler $scheduler): void
    {
        // The Kernel's schedules() hook is optional — only call it if a Loader
        // has been configured (e.g. host app booted, integration test). When
        // the command is exercised in isolation (unit tests) we still want the
        // routes/scheduler.php auto-include below to work.
        try {
            $kernel = Loader::getInstance();
            $kernel->schedules($scheduler);
        } catch (\Throwable) {
            // No Loader configured; skip the Kernel hook and continue.
        }

        $routes_file = $this->setting->getBaseDirectory() . '/routes/scheduler.php';
        if (is_file($routes_file)) {
            require $routes_file;
        }
    }

    /**
     * Display an event result
     *
     * @param  array $result
     * @return void
     */
    private function displayResult(array $result): void
    {
        $status = match ($result['status']) {
            'success' => Color::green('[SUCCESS]'),
            'failed' => Color::red('[FAILED]'),
            'skipped' => Color::yellow('[SKIPPED]'),
            default => Color::yellow('[UNKNOWN]'),
        };

        echo sprintf(
            "%s [%s] %s\n",
            $status,
            $result['type'],
            $result['description']
        );

        if ($result['error']) {
            echo Color::red("   Error: {$result['error']}\n");
        }

        if ($result['started_at'] && $result['finished_at']) {
            $duration = $result['finished_at']->getTimestamp() - $result['started_at']->getTimestamp();
            echo Color::yellow("   Duration: {$duration}s\n");
        }
    }
}
