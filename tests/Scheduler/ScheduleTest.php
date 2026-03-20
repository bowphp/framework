<?php

namespace Bow\Tests\Scheduler;

use DateTime;
use DateTimeZone;
use Bow\Scheduler\Schedule;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    private Schedule $schedule;

    protected function setUp(): void
    {
        $this->schedule = new Schedule();
    }

    public function test_default_expression_is_every_minute()
    {
        $this->assertEquals('* * * * *', $this->schedule->getExpression());
    }

    public function test_every_minute()
    {
        $this->schedule->everyMinute();
        $this->assertEquals('* * * * *', $this->schedule->getExpression());
    }

    public function test_every_two_minutes()
    {
        $this->schedule->everyTwoMinutes();
        $this->assertEquals('*/2 * * * *', $this->schedule->getExpression());
    }

    public function test_every_five_minutes()
    {
        $this->schedule->everyFiveMinutes();
        $this->assertEquals('*/5 * * * *', $this->schedule->getExpression());
    }

    public function test_every_ten_minutes()
    {
        $this->schedule->everyTenMinutes();
        $this->assertEquals('*/10 * * * *', $this->schedule->getExpression());
    }

    public function test_every_fifteen_minutes()
    {
        $this->schedule->everyFifteenMinutes();
        $this->assertEquals('*/15 * * * *', $this->schedule->getExpression());
    }

    public function test_every_thirty_minutes()
    {
        $this->schedule->everyThirtyMinutes();
        $this->assertEquals('0,30 * * * *', $this->schedule->getExpression());
    }

    public function test_hourly()
    {
        $this->schedule->hourly();
        $this->assertEquals('0 * * * *', $this->schedule->getExpression());
    }

    public function test_hourly_at()
    {
        $this->schedule->hourlyAt(15);
        $this->assertEquals('15 * * * *', $this->schedule->getExpression());
    }

    public function test_daily()
    {
        $this->schedule->daily();
        $this->assertEquals('0 0 * * *', $this->schedule->getExpression());
    }

    public function test_daily_at()
    {
        $this->schedule->dailyAt('13:30');
        $this->assertEquals('30 13 * * *', $this->schedule->getExpression());
    }

    public function test_daily_at_chained()
    {
        $this->schedule->dailyAt('14:45');
        $this->assertEquals('45 14 * * *', $this->schedule->getExpression());
    }

    public function test_twice_daily()
    {
        $this->schedule->twiceDaily(1, 13);
        $this->assertEquals('0 1,13 * * *', $this->schedule->getExpression());
    }

    public function test_weekly()
    {
        $this->schedule->weekly();
        $this->assertEquals('0 0 * * 0', $this->schedule->getExpression());
    }

    public function test_weekly_on()
    {
        $this->schedule->weeklyOn(1, '8:00');
        $this->assertEquals('0 8 * * 1', $this->schedule->getExpression());
    }

    public function test_monthly()
    {
        $this->schedule->monthly();
        $this->assertEquals('0 0 1 * *', $this->schedule->getExpression());
    }

    public function test_monthly_on()
    {
        $this->schedule->monthlyOn(15, '14:00');
        $this->assertEquals('0 14 15 * *', $this->schedule->getExpression());
    }

    public function test_yearly()
    {
        $this->schedule->yearly();
        $this->assertEquals('0 0 1 1 *', $this->schedule->getExpression());
    }

    public function test_cron_expression()
    {
        $this->schedule->cron('30 4 * * 1-5');
        $this->assertEquals('30 4 * * 1-5', $this->schedule->getExpression());
    }

    public function test_weekdays()
    {
        $this->schedule->daily()->weekdays();
        $this->assertEquals('0 0 * * 1-5', $this->schedule->getExpression());
    }

    public function test_weekends()
    {
        $this->schedule->daily()->weekends();
        $this->assertEquals('0 0 * * 0,6', $this->schedule->getExpression());
    }

    public function test_mondays()
    {
        $this->schedule->daily()->mondays();
        $this->assertEquals('0 0 * * 1', $this->schedule->getExpression());
    }

    public function test_tuesdays()
    {
        $this->schedule->daily()->tuesdays();
        $this->assertEquals('0 0 * * 2', $this->schedule->getExpression());
    }

    public function test_wednesdays()
    {
        $this->schedule->daily()->wednesdays();
        $this->assertEquals('0 0 * * 3', $this->schedule->getExpression());
    }

    public function test_thursdays()
    {
        $this->schedule->daily()->thursdays();
        $this->assertEquals('0 0 * * 4', $this->schedule->getExpression());
    }

    public function test_fridays()
    {
        $this->schedule->daily()->fridays();
        $this->assertEquals('0 0 * * 5', $this->schedule->getExpression());
    }

    public function test_saturdays()
    {
        $this->schedule->daily()->saturdays();
        $this->assertEquals('0 0 * * 6', $this->schedule->getExpression());
    }

    public function test_sundays()
    {
        $this->schedule->daily()->sundays();
        $this->assertEquals('0 0 * * 0', $this->schedule->getExpression());
    }

    public function test_days()
    {
        $this->schedule->daily()->days('1,3,5');
        $this->assertEquals('0 0 * * 1,3,5', $this->schedule->getExpression());
    }

    public function test_description()
    {
        $this->schedule->description('Test task');
        $this->assertEquals('Test task', $this->schedule->getDescription());
    }

    public function test_without_overlapping()
    {
        $this->schedule->withoutOverlapping(30);
        $this->assertTrue($this->schedule->shouldPreventOverlapping());
        $this->assertEquals(30, $this->schedule->getExpiresAt());
    }

    public function test_run_in_background()
    {
        $this->schedule->runInBackground();
        $this->assertTrue($this->schedule->shouldRunInBackground());
    }

    public function test_timezone()
    {
        $this->schedule->timezone('America/New_York');
        $this->assertEquals(new DateTimeZone('America/New_York'), $this->schedule->getTimezone());
    }

    public function test_is_due_every_minute()
    {
        $this->schedule->everyMinute();
        $this->assertTrue($this->schedule->isDue(new DateTime()));
    }

    public function test_is_due_specific_time()
    {
        $this->schedule->dailyAt('10:30');

        $dueTime = new DateTime('today 10:30');
        $notDueTime = new DateTime('today 11:00');

        $this->assertTrue($this->schedule->isDue($dueTime));
        $this->assertFalse($this->schedule->isDue($notDueTime));
    }

    public function test_when_filter()
    {
        $this->schedule->everyMinute()->when(function () {
            return true;
        });

        $this->assertTrue($this->schedule->filtersPass());
    }

    public function test_when_filter_fails()
    {
        $this->schedule->everyMinute()->when(function () {
            return false;
        });

        $this->assertFalse($this->schedule->filtersPass());
    }

    public function test_skip_filter()
    {
        $this->schedule->everyMinute()->skip(function () {
            return true;
        });

        $this->assertFalse($this->schedule->filtersPass());
    }

    public function test_skip_filter_passes()
    {
        $this->schedule->everyMinute()->skip(function () {
            return false;
        });

        $this->assertTrue($this->schedule->filtersPass());
    }

    public function test_fluent_api_chaining()
    {
        $schedule = $this->schedule
            ->dailyAt('09:00')
            ->weekdays()
            ->description('Daily report')
            ->withoutOverlapping(60);

        $this->assertSame($schedule, $this->schedule);
        $this->assertEquals('0 9 * * 1-5', $this->schedule->getExpression());
        $this->assertEquals('Daily report', $this->schedule->getDescription());
        $this->assertTrue($this->schedule->shouldPreventOverlapping());
    }

    public function test_is_due_hourly()
    {
        $this->schedule->hourly();

        $dueTime = new DateTime('today 14:00');
        $notDueTime = new DateTime('today 14:30');

        $this->assertTrue($this->schedule->isDue($dueTime));
        $this->assertFalse($this->schedule->isDue($notDueTime));
    }

    public function test_is_due_with_step()
    {
        $this->schedule->everyFiveMinutes();

        $dueTime = new DateTime('today 14:05');
        $notDueTime = new DateTime('today 14:03');

        $this->assertTrue($this->schedule->isDue($dueTime));
        $this->assertFalse($this->schedule->isDue($notDueTime));
    }
}
