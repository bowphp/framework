# Bow Scheduler

Define scheduled tasks in your `App\Kernel::schedules()` method:

```php
public function schedules(Scheduler $schedule): void
{
    $schedule->command('cache:clear')->daily();
    $schedule->exec('mysqldump mydb > backup.sql')->daily()->at('03:00');
    $schedule->call(fn () => logger('Heartbeat'))->everyMinute();
    $schedule->task(SendReportTask::class)->weekly()->sundays();
}
```

## Console Commands

```bash
php bow schedule:list   # List all tasks
php bow schedule:run    # Run due tasks once
php bow schedule:work   # Start daemon (continuous)
```

## Production (Cron)

```bash
* * * * * cd /path-to-project && php bow schedule:run >> /dev/null 2>&1
```
