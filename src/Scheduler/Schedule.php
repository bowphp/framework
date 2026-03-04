<?php

declare(strict_types=1);

namespace Bow\Scheduler;

use DateTimeInterface;
use DateTimeZone;

class Schedule
{
    /**
     * The cron expression representing the task's frequency
     *
     * @var string
     */
    protected string $expression = '* * * * *';

    /**
     * The timezone the date should be evaluated on
     *
     * @var ?DateTimeZone
     */
    protected ?DateTimeZone $timezone = null;

    /**
     * Indicates if overlapping should be prevented
     *
     * @var bool
     */
    protected bool $withoutOverlapping = false;

    /**
     * The number of minutes the mutex should be valid
     *
     * @var int
     */
    protected int $expiresAt = 1440;

    /**
     * Indicates if output should be appended
     *
     * @var bool
     */
    protected bool $runInBackground = false;

    /**
     * The array of callbacks to filter when the task should run
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * The array of callbacks to reject when the task should run
     *
     * @var array
     */
    protected array $rejects = [];

    /**
     * The description of the scheduled task
     *
     * @var ?string
     */
    protected ?string $description = null;

    /**
     * The owning scheduled event
     *
     * @var ?ScheduledEvent
     */
    protected ?ScheduledEvent $event = null;

    /**
     * Create a new schedule instance
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Run the task every minute
     *
     * @return $this
     */
    public function everyMinute(): static
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * Run the task every two minutes
     *
     * @return $this
     */
    public function everyTwoMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /**
     * Run the task every five minutes
     *
     * @return $this
     */
    public function everyFiveMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * Run the task every ten minutes
     *
     * @return $this
     */
    public function everyTenMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * Run the task every fifteen minutes
     *
     * @return $this
     */
    public function everyFifteenMinutes(): static
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * Run the task every thirty minutes
     *
     * @return $this
     */
    public function everyThirtyMinutes(): static
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * Run the task hourly
     *
     * @return $this
     */
    public function hourly(): static
    {
        return $this->spliceIntoPosition(1, '0');
    }

    /**
     * Run the task hourly at a given offset
     *
     * @param  array|int $offset
     * @return $this
     */
    public function hourlyAt(array|int $offset): static
    {
        $offset = is_array($offset) ? implode(',', $offset) : $offset;

        return $this->spliceIntoPosition(1, (string) $offset);
    }

    /**
     * Run the task every two hours
     *
     * @return $this
     */
    public function everyTwoHours(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '*/2');
    }

    /**
     * Run the task every three hours
     *
     * @return $this
     */
    public function everyThreeHours(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '*/3');
    }

    /**
     * Run the task every four hours
     *
     * @return $this
     */
    public function everyFourHours(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '*/4');
    }

    /**
     * Run the task every six hours
     *
     * @return $this
     */
    public function everySixHours(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '*/6');
    }

    /**
     * Run the task daily
     *
     * @return $this
     */
    public function daily(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '0');
    }

    /**
     * Run the task daily at a given time
     *
     * @param  string $time
     * @return $this
     */
    public function dailyAt(string $time): static
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
                    ->spliceIntoPosition(1, count($segments) === 2 ? (int) $segments[1] : '0');
    }

    /**
     * Run the task twice daily
     *
     * @param  int $first
     * @param  int $second
     * @return $this
     */
    public function twiceDaily(int $first = 1, int $second = 13): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, "{$first},{$second}");
    }

    /**
     * Run the task weekly
     *
     * @return $this
     */
    public function weekly(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '0')
                    ->spliceIntoPosition(5, '0');
    }

    /**
     * Run the task weekly on a given day and time
     *
     * @param  array|int $dayOfWeek
     * @param  string    $time
     * @return $this
     */
    public function weeklyOn(array|int $dayOfWeek, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        $dayOfWeek = is_array($dayOfWeek) ? implode(',', $dayOfWeek) : $dayOfWeek;

        return $this->spliceIntoPosition(5, (string) $dayOfWeek);
    }

    /**
     * Run the task monthly
     *
     * @return $this
     */
    public function monthly(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '0')
                    ->spliceIntoPosition(3, '1');
    }

    /**
     * Run the task monthly on a given day and time
     *
     * @param  int    $dayOfMonth
     * @param  string $time
     * @return $this
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, (string) $dayOfMonth);
    }

    /**
     * Run the task twice monthly
     *
     * @param  int    $first
     * @param  int    $second
     * @param  string $time
     * @return $this
     */
    public function twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, "{$first},{$second}");
    }

    /**
     * Run the task quarterly
     *
     * @return $this
     */
    public function quarterly(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '0')
                    ->spliceIntoPosition(3, '1')
                    ->spliceIntoPosition(4, '1,4,7,10');
    }

    /**
     * Run the task yearly
     *
     * @return $this
     */
    public function yearly(): static
    {
        return $this->spliceIntoPosition(1, '0')
                    ->spliceIntoPosition(2, '0')
                    ->spliceIntoPosition(3, '1')
                    ->spliceIntoPosition(4, '1');
    }

    /**
     * Run the task yearly on a given month, day, and time
     *
     * @param  int    $month
     * @param  int    $dayOfMonth
     * @param  string $time
     * @return $this
     */
    public function yearlyOn(int $month = 1, int $dayOfMonth = 1, string $time = '0:0'): static
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, (string) $dayOfMonth)
                    ->spliceIntoPosition(4, (string) $month);
    }

    /**
     * Schedule the task to run on given days of the week
     *
     * @param  array|int|string $days
     * @return $this
     */
    public function days(array|int|string $days): static
    {
        $days = is_array($days) ? implode(',', $days) : $days;

        return $this->spliceIntoPosition(5, (string) $days);
    }

    /**
     * Schedule the task to run on Mondays
     *
     * @return $this
     */
    public function mondays(): static
    {
        return $this->days(1);
    }

    /**
     * Schedule the task to run on Tuesdays
     *
     * @return $this
     */
    public function tuesdays(): static
    {
        return $this->days(2);
    }

    /**
     * Schedule the task to run on Wednesdays
     *
     * @return $this
     */
    public function wednesdays(): static
    {
        return $this->days(3);
    }

    /**
     * Schedule the task to run on Thursdays
     *
     * @return $this
     */
    public function thursdays(): static
    {
        return $this->days(4);
    }

    /**
     * Schedule the task to run on Fridays
     *
     * @return $this
     */
    public function fridays(): static
    {
        return $this->days(5);
    }

    /**
     * Schedule the task to run on Saturdays
     *
     * @return $this
     */
    public function saturdays(): static
    {
        return $this->days(6);
    }

    /**
     * Schedule the task to run on Sundays
     *
     * @return $this
     */
    public function sundays(): static
    {
        return $this->days(0);
    }

    /**
     * Schedule the task to run on weekdays
     *
     * @return $this
     */
    public function weekdays(): static
    {
        return $this->days('1-5');
    }

    /**
     * Schedule the task to run on weekends
     *
     * @return $this
     */
    public function weekends(): static
    {
        return $this->days('0,6');
    }

    /**
     * Set the cron expression with a custom expression
     *
     * @param  string $expression
     * @return $this
     */
    public function cron(string $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Set the timezone the date should be evaluated on
     *
     * @param  DateTimeZone|string $timezone
     * @return $this
     */
    public function timezone(DateTimeZone|string $timezone): static
    {
        $this->timezone = $timezone instanceof DateTimeZone
            ? $timezone
            : new DateTimeZone($timezone);

        return $this;
    }

    /**
     * Indicate that the job should run in background
     *
     * @return $this
     */
    public function runInBackground(): static
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * Indicate that overlapping should be prevented
     *
     * @param  int $expiresAt
     * @return $this
     */
    public function withoutOverlapping(int $expiresAt = 1440): static
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule
     *
     * @param  callable $callback
     * @return $this
     */
    public function when(callable $callback): static
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule
     *
     * @param  callable $callback
     * @return $this
     */
    public function skip(callable $callback): static
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Set the description of the scheduled task
     *
     * @param  string $description
     * @return $this
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the cron expression
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get the timezone
     *
     * @return ?DateTimeZone
     */
    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Get the description
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Determine if the task should prevent overlapping
     *
     * @return bool
     */
    public function shouldPreventOverlapping(): bool
    {
        return $this->withoutOverlapping;
    }

    /**
     * Get the expires at value
     *
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Check if the task should run in background
     *
     * @return bool
     */
    public function shouldRunInBackground(): bool
    {
        return $this->runInBackground;
    }

    /**
     * Determine if the filters pass for the task
     *
     * @return bool
     */
    public function filtersPass(): bool
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the task is due to run
     *
     * @param  DateTimeInterface $currentTime
     * @return bool
     */
    public function isDue(DateTimeInterface $currentTime): bool
    {
        $dateParts = $this->getDateParts($currentTime);
        $cronParts = explode(' ', $this->expression);

        if (count($cronParts) !== 5) {
            return false;
        }

        return $this->matchesCronPart($cronParts[0], $dateParts['minute']) &&
               $this->matchesCronPart($cronParts[1], $dateParts['hour']) &&
               $this->matchesCronPart($cronParts[2], $dateParts['day']) &&
               $this->matchesCronPart($cronParts[3], $dateParts['month']) &&
               $this->matchesCronPart($cronParts[4], $dateParts['weekday']);
    }

    /**
     * Get the date parts from a DateTime
     *
     * @param  DateTimeInterface $date
     * @return array
     */
    protected function getDateParts(DateTimeInterface $date): array
    {
        $timezone = $this->timezone ?? $date->getTimezone();


        $date = \DateTime::createFromInterface($date)->setTimezone($timezone);

        return [
            'minute'  => (int) $date->format('i'),
            'hour'    => (int) $date->format('G'),
            'day'     => (int) $date->format('j'),
            'month'   => (int) $date->format('n'),
            'weekday' => (int) $date->format('w'),
        ];
    }

    /**
     * Check if a cron part matches the given value
     *
     * @param  string $cronPart
     * @param  int    $value
     * @return bool
     */
    protected function matchesCronPart(string $cronPart, int $value): bool
    {
        // Match any value
        if ($cronPart === '*') {
            return true;
        }

        // Handle step values (e.g., */5)
        if (str_starts_with($cronPart, '*/')) {
            $step = (int) substr($cronPart, 2);
            return $step > 0 && $value % $step === 0;
        }

        // Handle ranges (e.g., 1-5)
        if (str_contains($cronPart, '-')) {
            [$start, $end] = explode('-', $cronPart);
            return $value >= (int) $start && $value <= (int) $end;
        }

        // Handle lists (e.g., 1,3,5)
        if (str_contains($cronPart, ',')) {
            $parts = array_map('intval', explode(',', $cronPart));
            return in_array($value, $parts, true);
        }

        // Direct match
        return (int) $cronPart === $value;
    }

    /**
     * Splice a value into the cron expression
     *
     * @param  int              $position
     * @param  int|string       $value
     * @return $this
     */
    protected function spliceIntoPosition(int $position, int|string $value): static
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = (string) $value;

        $this->expression = implode(' ', $segments);

        return $this;
    }

    /**
     * Set the owning scheduled event
     *
     * @param  ScheduledEvent $event
     * @return $this
     */
    public function setEvent(ScheduledEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get the owning scheduled event
     *
     * @return ?ScheduledEvent
     */
    public function getEvent(): ?ScheduledEvent
    {
        return $this->event;
    }

    /**
     * Set the queue connection to use for task execution
     *
     * @param  string $connection
     * @return $this
     */
    public function onConnection(string $connection): static
    {
        if ($this->event) {
            $this->event->onConnection($connection);
        }

        return $this;
    }
}
