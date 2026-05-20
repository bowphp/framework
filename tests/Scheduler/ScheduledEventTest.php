<?php

namespace Bow\Tests\Scheduler;

use DateTime;
use Bow\Scheduler\Schedule;
use Bow\Scheduler\ScheduledEvent;
use Bow\Scheduler\Exceptions\SchedulerException;
use Bow\Tests\Scheduler\Stubs\TestQueueTaskStub;
use PHPUnit\Framework\TestCase;

class ScheduledEventTest extends TestCase
{
    protected function setUp(): void
    {
        TestQueueTaskStub::reset();
    }

    public function test_create_command_event()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'cache:clear');

        $this->assertEquals(ScheduledEvent::TYPE_COMMAND, $event->getType());
        $this->assertEquals('cache:clear', $event->getTarget());
        $this->assertInstanceOf(Schedule::class, $event->getSchedule());
    }

    public function test_create_exec_event()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_EXEC, 'ls -la');

        $this->assertEquals(ScheduledEvent::TYPE_EXEC, $event->getType());
        $this->assertEquals('ls -la', $event->getTarget());
    }

    public function test_create_call_event()
    {
        $callback = function () {
            return 'test';
        };

        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, $callback);

        $this->assertEquals(ScheduledEvent::TYPE_CALL, $event->getType());
        $this->assertSame($callback, $event->getTarget());
    }

    public function test_create_task_event()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_TASK, TestQueueTaskStub::class);

        $this->assertEquals(ScheduledEvent::TYPE_TASK, $event->getType());
        $this->assertEquals(TestQueueTaskStub::class, $event->getTarget());
    }

    public function test_get_schedule_returns_schedule_instance()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'test');

        $this->assertInstanceOf(Schedule::class, $event->getSchedule());
    }

    public function test_schedule_event_reference()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'test');

        $this->assertSame($event, $event->getSchedule()->getEvent());
    }

    public function test_is_due_with_every_minute()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->getSchedule()->everyMinute();

        $this->assertTrue($event->isDue());
    }

    public function test_is_due_with_specific_time()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->getSchedule()->dailyAt('10:30');

        $dueTime = new DateTime('today 10:30');
        $notDueTime = new DateTime('today 11:00');

        $this->assertTrue($event->isDue($dueTime));
        $this->assertFalse($event->isDue($notDueTime));
    }

    public function test_get_cron_expression()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->getSchedule()->dailyAt('09:00');

        $this->assertEquals('0 9 * * *', $event->getCronExpression());
    }

    public function test_get_mutex_name_for_command()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'cache:clear');

        $this->assertStringStartsWith('scheduler:', $event->getMutexName());
    }

    public function test_custom_mutex_name()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'test');
        $event->setMutexName('custom-mutex');

        $this->assertEquals('custom-mutex', $event->getMutexName());
    }

    public function test_execute_call_event()
    {
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;
        };

        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, $callback);
        $event->run();

        $this->assertTrue($executed);
    }

    public function test_execute_call_event_with_parameters()
    {
        $result = null;
        $callback = function ($name, $value) use (&$result) {
            $result = "{$name}:{$value}";
        };

        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, $callback, ['test', 123]);
        $event->run();

        $this->assertEquals('test:123', $result);
    }

    public function test_execute_exec_event()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_EXEC, 'echo "hello"');
        $event->run();

        $this->assertEquals('hello', trim($event->getOutput()));
        $this->assertEquals(0, $event->getExitCode());
    }

    public function test_before_callback()
    {
        $beforeCalled = false;
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->before(function () use (&$beforeCalled) {
            $beforeCalled = true;
        });

        $event->runBeforeCallback();

        $this->assertTrue($beforeCalled);
    }

    public function test_after_callback()
    {
        $afterCalled = false;
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->after(function () use (&$afterCalled) {
            $afterCalled = true;
        });

        $event->runAfterCallback();

        $this->assertTrue($afterCalled);
    }

    public function test_on_failure_callback()
    {
        $failedCalled = false;
        $capturedEvent = null;
        $capturedException = null;

        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);
        $event->onFailure(function ($e, $exception) use (&$failedCalled, &$capturedEvent, &$capturedException) {
            $failedCalled = true;
            $capturedEvent = $e;
            $capturedException = $exception;
        });

        $exception = new \Exception('Test error');
        $event->runFailedCallback($exception);

        $this->assertTrue($failedCalled);
        $this->assertSame($event, $capturedEvent);
        $this->assertSame($exception, $capturedException);
    }

    public function test_get_last_run_at()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);

        $this->assertNull($event->getLastRunAt());

        $event->run();

        $this->assertInstanceOf(DateTime::class, $event->getLastRunAt());
    }

    public function test_is_running()
    {
        $runningState = null;
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, function () use (&$runningState, &$event) {
            $runningState = $event->isRunning();
        });

        $this->assertFalse($event->isRunning());
        $event->run();
        $this->assertTrue($runningState);
        $this->assertFalse($event->isRunning());
    }

    public function test_get_description_for_command()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'cache:clear');

        $this->assertEquals('php bow cache:clear', $event->getDescription());
    }

    public function test_get_description_for_exec()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_EXEC, 'ls -la');

        $this->assertEquals('ls -la', $event->getDescription());
    }

    public function test_get_description_for_call()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, fn() => null);

        $this->assertEquals('Closure', $event->getDescription());
    }

    public function test_get_description_for_task()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_TASK, TestQueueTaskStub::class);

        $this->assertEquals(TestQueueTaskStub::class, $event->getDescription());
    }

    public function test_custom_description_takes_priority()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_COMMAND, 'cache:clear');
        $event->getSchedule()->description('Custom description');

        $this->assertEquals('Custom description', $event->getDescription());
    }

    public function test_on_connection()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_TASK, TestQueueTaskStub::class);
        $event->onConnection('redis');

        $this->assertEquals('redis', $event->getConnection());
    }

    public function test_on_connection_via_schedule()
    {
        $event = new ScheduledEvent(ScheduledEvent::TYPE_TASK, TestQueueTaskStub::class);
        $event->getSchedule()->onConnection('database');

        $this->assertEquals('database', $event->getConnection());
    }

    public function test_throws_for_already_running()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Event is already running');

        $event = new ScheduledEvent(ScheduledEvent::TYPE_CALL, function () use (&$event) {
            // Try to run again while already running
            $event->run();
        });

        $event->run();
    }

    public function test_throws_for_invalid_task_class()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Task class [NonExistentClass] does not exist');

        // Create a mock that skips queue push
        $event = new class (ScheduledEvent::TYPE_TASK, 'NonExistentClass') extends ScheduledEvent {
            protected function pushToQueue(\Bow\Queue\QueueTask $task): void
            {
                // Skip actual queue push in test
            }
        };

        $event->run();
    }

    public function test_throws_for_non_queue_task_instance()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Task must be an instance of');

        // Create a mock that skips queue push
        $event = new class (ScheduledEvent::TYPE_TASK, new \stdClass()) extends ScheduledEvent {
            protected function pushToQueue(\Bow\Queue\QueueTask $task): void
            {
                // Skip actual queue push in test
            }
        };

        $event->run();
    }
}
