<?php

namespace Bow\Tests\Scheduler;

use DateTime;
use Bow\Scheduler\Schedule;
use Bow\Scheduler\Scheduler;
use Bow\Scheduler\ScheduledEvent;
use Bow\Tests\Scheduler\Stubs\TestQueueTaskStub;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the singleton for each test
        Scheduler::reset();
        TestQueueTaskStub::reset();
    }

    protected function tearDown(): void
    {
        Scheduler::reset();
    }

    public function test_get_instance_returns_singleton()
    {
        $instance1 = Scheduler::getInstance();
        $instance2 = Scheduler::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_command_returns_schedule()
    {
        $scheduler = Scheduler::getInstance();
        $schedule = $scheduler->command('cache:clear');

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_command_registers_event()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->command('cache:clear');

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
        $this->assertEquals(ScheduledEvent::TYPE_COMMAND, $events[0]->getType());
        $this->assertEquals('cache:clear', $events[0]->getTarget());
    }

    public function test_command_with_parameters()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->command('email:send', ['--to' => 'admin@example.com']);

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
    }

    public function test_exec_returns_schedule()
    {
        $scheduler = Scheduler::getInstance();
        $schedule = $scheduler->exec('ls -la');

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_exec_registers_event()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->exec('ls -la');

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
        $this->assertEquals(ScheduledEvent::TYPE_EXEC, $events[0]->getType());
        $this->assertEquals('ls -la', $events[0]->getTarget());
    }

    public function test_call_returns_schedule()
    {
        $scheduler = Scheduler::getInstance();
        $schedule = $scheduler->call(function () {
            return 'test';
        });

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_call_registers_event()
    {
        $scheduler = Scheduler::getInstance();
        $callback = function () {
            return 'test';
        };
        $scheduler->call($callback);

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
        $this->assertEquals(ScheduledEvent::TYPE_CALL, $events[0]->getType());
    }

    public function test_call_with_parameters()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->call(function ($name, $value) {
            return "{$name}:{$value}";
        }, ['test', 123]);

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
    }

    public function test_task_returns_schedule()
    {
        $scheduler = Scheduler::getInstance();
        $schedule = $scheduler->task(TestQueueTaskStub::class);

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_task_registers_event()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->task(TestQueueTaskStub::class);

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
        $this->assertEquals(ScheduledEvent::TYPE_TASK, $events[0]->getType());
        $this->assertEquals(TestQueueTaskStub::class, $events[0]->getTarget());
    }

    public function test_task_with_instance()
    {
        $scheduler = Scheduler::getInstance();
        $task = new TestQueueTaskStub('test-data');
        $scheduler->task($task);

        $events = $scheduler->getEvents();

        $this->assertCount(1, $events);
        $this->assertSame($task, $events[0]->getTarget());
    }

    public function test_get_events_returns_all_events()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->command('cache:clear');
        $scheduler->exec('ls -la');
        $scheduler->call(fn() => null);
        $scheduler->task(TestQueueTaskStub::class);

        $events = $scheduler->getEvents();

        $this->assertCount(4, $events);
    }

    public function test_get_due_events()
    {
        $scheduler = Scheduler::getInstance();

        // Event that is always due
        $scheduler->call(fn() => null)->everyMinute();

        // Event that is never due (far in the future)
        $scheduler->call(fn() => null)->cron('0 0 1 1 0'); // Jan 1st at midnight on Sunday

        $dueEvents = $scheduler->getDueEvents();

        $this->assertCount(1, $dueEvents);
    }

    public function test_get_due_events_with_specific_time()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler->call(fn() => null)->dailyAt('10:30');
        $scheduler->call(fn() => null)->dailyAt('14:00');

        $dueAt1030 = $scheduler->getDueEvents(new DateTime('today 10:30'));
        $dueAt1400 = $scheduler->getDueEvents(new DateTime('today 14:00'));

        $this->assertCount(1, $dueAt1030);
        $this->assertCount(1, $dueAt1400);
    }

    public function test_run_executes_due_events()
    {
        $scheduler = Scheduler::getInstance();
        $executed = false;

        $scheduler->call(function () use (&$executed) {
            $executed = true;
        })->everyMinute();

        $results = $scheduler->run();

        $this->assertTrue($executed);
        $this->assertCount(1, $results);
        $this->assertEquals('success', $results[0]['status']);
    }

    public function test_run_returns_results_array()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler->call(fn() => null)->everyMinute()->description('Test task');

        $results = $scheduler->run();

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('status', $results[0]);
        $this->assertArrayHasKey('type', $results[0]);
        $this->assertArrayHasKey('description', $results[0]);
        $this->assertArrayHasKey('started_at', $results[0]);
        $this->assertArrayHasKey('finished_at', $results[0]);
    }

    public function test_run_with_failed_event()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler->call(function () {
            throw new \Exception('Test error');
        })->everyMinute();

        $results = $scheduler->run();

        $this->assertCount(1, $results);
        $this->assertEquals('failed', $results[0]['status']);
        $this->assertEquals('Test error', $results[0]['error']);
    }

    public function test_run_executes_before_and_after_callbacks()
    {
        $scheduler = Scheduler::getInstance();
        $beforeCalled = false;
        $afterCalled = false;

        $schedule = $scheduler->call(fn() => null)->everyMinute();

        // Access the event to set callbacks
        $events = $scheduler->getEvents();
        $events[0]->before(function () use (&$beforeCalled) {
            $beforeCalled = true;
        });
        $events[0]->after(function () use (&$afterCalled) {
            $afterCalled = true;
        });

        $scheduler->run();

        $this->assertTrue($beforeCalled);
        $this->assertTrue($afterCalled);
    }

    public function test_run_executes_failure_callback_on_error()
    {
        $scheduler = Scheduler::getInstance();
        $failedCalled = false;

        $scheduler->call(function () {
            throw new \Exception('Test error');
        })->everyMinute();

        $events = $scheduler->getEvents();
        $events[0]->onFailure(function () use (&$failedCalled) {
            $failedCalled = true;
        });

        $scheduler->run();

        $this->assertTrue($failedCalled);
    }

    public function test_clear_removes_all_events()
    {
        $scheduler = Scheduler::getInstance();
        $scheduler->command('cache:clear');
        $scheduler->exec('ls -la');

        $this->assertCount(2, $scheduler->getEvents());

        $scheduler->clear();

        $this->assertCount(0, $scheduler->getEvents());
    }

    public function test_clear_returns_self()
    {
        $scheduler = Scheduler::getInstance();

        $result = $scheduler->clear();

        $this->assertSame($scheduler, $result);
    }

    public function test_set_logger()
    {
        $scheduler = Scheduler::getInstance();
        $loggedMessages = [];

        $scheduler->setLogger(function ($message) use (&$loggedMessages) {
            $loggedMessages[] = $message;
        });

        $scheduler->call(fn() => null)->everyMinute()->description('Test task');
        $scheduler->run();

        $this->assertNotEmpty($loggedMessages);
    }

    public function test_enable_logging_can_disable()
    {
        $scheduler = Scheduler::getInstance();
        $loggedMessages = [];

        $scheduler->setLogger(function ($message) use (&$loggedMessages) {
            $loggedMessages[] = $message;
        });
        $scheduler->enableLogging(false);

        $scheduler->call(fn() => null)->everyMinute();
        $scheduler->run();

        $this->assertEmpty($loggedMessages);
    }

    public function test_fluent_api()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler
            ->command('cache:clear')
            ->dailyAt('02:00')
            ->description('Clear cache daily');

        $scheduler
            ->exec('backup.sh')
            ->weekly()
            ->sundays()
            ->dailyAt('03:00')
            ->description('Weekly backup');

        $events = $scheduler->getEvents();

        $this->assertCount(2, $events);
        $this->assertEquals('0 2 * * *', $events[0]->getCronExpression());
        $this->assertEquals('0 3 * * 0', $events[1]->getCronExpression());
    }

    public function test_multiple_events_with_different_schedules()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler->call(fn() => null)->everyMinute();
        $scheduler->call(fn() => null)->hourly();
        $scheduler->call(fn() => null)->daily();

        $events = $scheduler->getEvents();

        $this->assertEquals('* * * * *', $events[0]->getCronExpression());
        $this->assertEquals('0 * * * *', $events[1]->getCronExpression());
        $this->assertEquals('0 0 * * *', $events[2]->getCronExpression());
    }

    public function test_run_with_no_due_events()
    {
        $scheduler = Scheduler::getInstance();

        // Event scheduled for a time that won't be due
        $scheduler->call(fn() => null)->cron('0 0 1 1 0'); // Jan 1st at midnight on Sunday

        $results = $scheduler->run();

        $this->assertCount(0, $results);
    }

    public function test_task_with_on_connection()
    {
        $scheduler = Scheduler::getInstance();

        $scheduler->task(TestQueueTaskStub::class)
            ->daily()
            ->onConnection('redis');

        $events = $scheduler->getEvents();

        $this->assertEquals('redis', $events[0]->getConnection());
    }

    public function test_reset_creates_new_instance()
    {
        $instance1 = Scheduler::getInstance();
        $instance1->command('test');

        Scheduler::reset();

        $instance2 = Scheduler::getInstance();

        $this->assertNotSame($instance1, $instance2);
        $this->assertCount(0, $instance2->getEvents());
    }
}
