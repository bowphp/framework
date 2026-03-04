# Bow Scheduler

The Bow Scheduler provides a simple and elegant way to define scheduled tasks. It offers four main methods to schedule different types of jobs:

- **`command()`** - Run Bow console commands
- **`task()`** - Run QueueTask classes
- **`exec()`** - Run bash/shell commands
- **`call()`** - Run closures/callbacks

## Configuration

Define your scheduled events in the `routes/scheduler.php` file:

```php
<?php

use Bow\Scheduler\Scheduler;

// Get the scheduler instance
$scheduler = Scheduler::getInstance();

// Schedule a Bow console command
$scheduler->command('cache:clear')
    ->daily()
    ->dailyAt('02:00')
    ->description('Clear application cache');

// Schedule a shell command
$scheduler->exec('mysqldump -u root mydb > /backups/db.sql')
    ->daily()
    ->dailyAt('03:00')
    ->description('Backup database')
    ->runInBackground();

// Schedule a closure
$scheduler->call(function () {
    logger('Cleanup task ran...');
})
    ->hourly()
    ->description('Cleanup task');

// Schedule a QueueTask
$scheduler->task(App\Tasks\SendWeeklyReportTask::class)
    ->weekly()
    ->sundays()
    ->dailyAt('10:00')
    ->onConnection('redis')
    ->description('Send weekly reports');
```

The scheduler automatically loads this file when running scheduler commands.

## Available Methods

### `command(string $command, array $parameters = []): Schedule`

Schedule a Bow console command to run:

```php
// Simple command
$scheduler->command('migration:migrate')->daily();

// Command with parameters
$scheduler->command('email:send', ['--to' => 'admin@example.com'])->hourly();
```

### `task(string|QueueTask $task, array $parameters = []): Schedule`

Schedule a QueueTask class to run:

```php
// By class name
$scheduler->task(App\Tasks\ProcessReportsTask::class)->daily();

// With constructor parameters
$scheduler->task(App\Tasks\SendNotificationTask::class, ['user', 'message'])->hourly();

// With an instance
$task = new App\Tasks\GenerateStats($config);
$scheduler->task($task)->weekly();
```

### `exec(string $command, array $parameters = []): Schedule`

Schedule a shell/bash command:

```php
$scheduler->exec('rm -rf /tmp/cache/*')->daily()->at('04:00');

// With parameters (automatically escaped)
$scheduler->exec('tar -czf backup.tar.gz', ['/var/www/files'])->weekly();
```

### `call(callable $callback, array $parameters = []): Schedule`

Schedule a closure or callback:

```php
$scheduler->call(function () {
    // Your code here
})->everyFiveMinutes();

// With parameters
$scheduler->call(function ($name, $email) {
    logger("Processing: {$name} ({$email})");
}, ['John', 'john@example.com'])->hourly();
```

## Schedule Frequencies

Available frequency methods:

| Method | Description |
|--------|-------------|
| `everyMinute()` | Run every minute |
| `everyTwoMinutes()` | Run every 2 minutes |
| `everyFiveMinutes()` | Run every 5 minutes |
| `everyTenMinutes()` | Run every 10 minutes |
| `everyFifteenMinutes()` | Run every 15 minutes |
| `everyThirtyMinutes()` | Run every 30 minutes |
| `hourly()` | Run every hour |
| `hourlyAt(17)` | Run hourly at 17 minutes past |
| `daily()` | Run daily at midnight |
| `dailyAt('13:00')` | Run daily at 13:00 |
| `twiceDaily(1, 13)` | Run daily at 1:00 and 13:00 |
| `weekly()` | Run weekly on Sunday |
| `weeklyOn(1, '8:00')` | Run weekly on Monday at 8:00 |
| `monthly()` | Run monthly on the 1st at midnight |
| `monthlyOn(15, '15:00')` | Run monthly on the 15th at 15:00 |
| `quarterly()` | Run quarterly |
| `yearly()` | Run yearly |
| `cron('* * * * *')` | Define a custom cron expression |

### Day Constraints

```php
$scheduler->command('report:generate')
    ->daily()
    ->weekdays();  // Only on weekdays

$scheduler->command('cleanup')
    ->daily()
    ->weekends();  // Only on weekends

$scheduler->command('backup')
    ->daily()
    ->mondays();   // Only on Mondays
```

## Advanced Options

### Background Execution

For long-running commands, run in the background:

```php
$scheduler->exec('php process-heavy-task.php')
    ->daily()
    ->runInBackground();
```

### Overlap Prevention

Prevent a scheduled event from running if a previous instance is still running:

```php
$scheduler->command('slow:process')
    ->hourly()
    ->withoutOverlapping(60);  // Lock expires after 60 minutes
```

### Conditional Scheduling

Run events only when conditions are met:

```php
$scheduler->command('send:emails')
    ->daily()
    ->when(function () {
        return app()->environment('production');
    });

$scheduler->command('debug:task')
    ->daily()
    ->skip(function () {
        return app()->environment('production');
    });
```

### Event Callbacks

Execute callbacks before, after, or on failure:

```php
$scheduler->command('important:task')
    ->daily()
    ->before(function ($event) {
        logger('Starting important task...');
    })
    ->after(function ($event) {
        logger('Task completed!');
    })
    ->onFailure(function ($event, $exception) {
        logger("Task failed: " . $exception->getMessage());
    });
```

### Timezone

Set a specific timezone for the schedule:

```php
$scheduler->command('report:generate')
    ->daily()
    ->at('09:00')
    ->timezone('America/New_York');
```

## Console Commands

### Run Due Events

Run all events that are due:

```bash
php bow schedule:run
```

### Start Scheduler Daemon

Start a continuous scheduler (typically in production):

```bash
php bow schedule:work
```

### List Events

List all registered scheduled events:

```bash
php bow schedule:list
```

### Show Next Run Times

Show when each event will next run:

```bash
php bow schedule:next
```

### Test an Event

Test run an event by its index (0-based):

```bash
php bow schedule:test 0
```

## Production Setup

In production, add a cron entry that runs the scheduler every minute:

```bash
* * * * * cd /path-to-your-project && php bow schedule:run >> /dev/null 2>&1
```

Or use the daemon mode (recommended for better performance):

```bash
php bow schedule:work
```

For daemon mode, consider using a process manager like Supervisor:

```ini
[program:scheduler]
process_name=%(program_name)s
directory=/var/www/your-app
command=php bow schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/scheduler.log
```

## Example: Complete Application Setup

```php
<?php

// app/Configurations/SchedulerConfiguration.php

namespace App\Configurations;

use Bow\Configuration\Configuration;
use Bow\Scheduler\Scheduler;

class SchedulerConfiguration extends Configuration
{
    public function create(\Bow\Configuration\Loader $config): void
    {
        // Nothing to configure
    }

    public function run(): void
    {
        $scheduler = app('scheduler');

        // Daily database backup
        $scheduler->exec('mysqldump mydb > /backups/daily.sql')
            ->daily()
            ->at('01:00')
            ->description('Daily database backup');

        // Clear cache every Sunday
        $scheduler->command('cache:clear')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->description('Weekly cache clear');

        // Process pending reports every hour
        $scheduler->task(\App\Tasks\ProcessPendingReportsTask::class)
            ->hourly()
            ->description('Process pending reports');

        // Check system health every 5 minutes
        $scheduler->call(function () {
            $health = \App\Services\HealthChecker::check();
            if (!$health->isHealthy()) {
                \App\Services\AlertService::notify($health);
            }
        })
            ->everyFiveMinutes()
            ->description('Health check');
    }
}
```
