<?php

namespace Bow\Tests\Events;

use Bow\Database\Database;
use Bow\Event\Event;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Events\Stubs\EventModelStub;
use Bow\Tests\Events\Stubs\UserEventListenerStub;
use Bow\Tests\Events\Stubs\UserEventStub;
use PHPUnit\Framework\Assert;

class EventTest extends \PHPUnit\Framework\TestCase
{
    private static string $cache_filename;
    private Event $event;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);
        Database::connection("mysql");
        Database::connection("mysql")->statement('drop table if exists events');
        Database::connection("mysql")->statement('create table if not exists events (id int primary key, name varchar(255))');
        Database::connection("mysql")->statement("insert into events values (1, 'fluffy'), (2, 'dolly')");

        static::$cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';
    }

    protected function setUp(): void
    {
        $this->event = Event::getInstance();

        // Clear previous event registrations
        $this->event->off('user.destroy');
        $this->event->off('user.created');
        $this->event->off('user.updated');
        $this->event->off(UserEventStub::class);

        // Clean cache file
        if (file_exists(static::$cache_filename)) {
            file_put_contents(static::$cache_filename, '');
        }
    }

    public function test_event_can_be_registered_with_closure()
    {
        $called = false;

        $this->event->on('user.created', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($this->event->bound('user.created'));
        $this->event->emit('user.created');
        $this->assertTrue($called);
    }

    public function test_event_can_be_registered_with_listener_class()
    {
        $this->event->on(UserEventStub::class, UserEventListenerStub::class);

        $this->assertTrue($this->event->bound(UserEventStub::class));
    }

    public function test_event_can_emit_with_closure()
    {
        $result = null;

        $this->event->on('user.destroy', function (string $name) use (&$result) {
            $result = $name;
        });

        $this->event->emit('user.destroy', 'destroy');
        $this->assertEquals('destroy', $result);
    }

    public function test_event_can_emit_with_app_event()
    {
        $this->event->on(UserEventStub::class, UserEventListenerStub::class);

        $this->assertTrue($this->event->bound(UserEventStub::class), "Event should be bound");

        $result = UserEventStub::dispatch("papac");

        $this->assertNotNull($result, "Dispatch should return a result");

        $content = file_get_contents(static::$cache_filename);
        $this->assertEquals("papac", $content, "File should contain 'papac', got: '$content'");
    }

    public function test_event_bound_returns_false_for_unregistered_event()
    {
        $this->assertFalse($this->event->bound('user.updated'));
        $this->assertFalse($this->event->bound('nonexistent.event'));
    }

    public function test_event_listener_alias_works()
    {
        $called = false;

        $this->event->listener('user.test', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($this->event->bound('user.test'));
        $this->event->emit('user.test');
        $this->assertTrue($called);
    }

    public function test_event_once_registers_one_time_listener()
    {
        file_put_contents(static::$cache_filename, 'initial');

        $this->event->once('user.once', function () {
            file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt', 'once-called');
        });

        $this->assertTrue($this->event->bound('user.once'));
        $this->event->emit('user.once');
        $this->assertEquals('once-called', file_get_contents(static::$cache_filename));
    }

    public function test_event_off_removes_listener()
    {
        $this->event->on('user.test', function () {
        });
        $this->assertTrue($this->event->bound('user.test'));

        $this->event->off('user.test');
        $this->assertFalse($this->event->bound('user.test'));
    }

    public function test_event_off_works_with_app_event()
    {
        $this->event->on(UserEventStub::class, UserEventListenerStub::class);
        $this->assertTrue($this->event->bound(UserEventStub::class));

        $this->event->off(UserEventStub::class);
        $this->assertFalse($this->event->bound(UserEventStub::class));
    }

    public function test_event_dispatch_is_alias_for_emit()
    {
        $called = false;

        $this->event->on('user.dispatch', function () use (&$called) {
            $called = true;
        });

        $this->event->dispatch('user.dispatch');
        $this->assertTrue($called);
    }

    public function test_event_priority_orders_listeners_correctly()
    {
        $order = [];

        $this->event->on('user.priority', function () use (&$order) {
            $order[] = 'low';
        }, 1);

        $this->event->on('user.priority', function () use (&$order) {
            $order[] = 'high';
        }, 10);

        $this->event->on('user.priority', function () use (&$order) {
            $order[] = 'medium';
        }, 5);

        $this->event->emit('user.priority');

        $this->assertEquals(['high', 'medium', 'low'], $order);
    }

    public function test_event_can_pass_multiple_arguments()
    {
        $receivedArgs = [];

        $this->event->on('user.args', function ($arg1, $arg2, $arg3) use (&$receivedArgs) {
            $receivedArgs = [$arg1, $arg2, $arg3];
        });

        $this->event->emit('user.args', 'first', 'second', 'third');

        $this->assertEquals(['first', 'second', 'third'], $receivedArgs);
    }

    public function test_event_emit_returns_null_for_unbound_event()
    {
        $result = $this->event->emit('nonexistent.event');

        $this->assertNull($result);
    }

    public function test_event_emit_returns_true_for_successful_emission()
    {
        $this->event->on('user.success', function () {
        });

        $result = $this->event->emit('user.success');

        $this->assertTrue($result);
    }

    public function test_multiple_listeners_on_same_event()
    {
        $count = 0;

        $this->event->on('user.multiple', function () use (&$count) {
            $count++;
        });

        $this->event->on('user.multiple', function () use (&$count) {
            $count++;
        });

        $this->event->on('user.multiple', function () use (&$count) {
            $count++;
        });

        $this->event->emit('user.multiple');

        $this->assertEquals(3, $count);
    }

    public function test_get_event_listeners_returns_array()
    {
        $this->event->on('user.listeners', function () {
        });

        $listeners = $this->event->getEventListeners('user.listeners');

        $this->assertIsArray($listeners);
        $this->assertCount(1, $listeners);
    }

    public function test_get_event_listeners_returns_empty_array_for_unbound()
    {
        $listeners = $this->event->getEventListeners('nonexistent.event');

        $this->assertIsArray($listeners);
        $this->assertCount(0, $listeners);
    }

    public function test_model_created_event_is_emitted()
    {
        file_put_contents(static::$cache_filename, '');

        EventModelStub::created(function ($model) {
            file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt', 'created');
        });

        $event = EventModelStub::connection("mysql");
        $event->setAttributes([
            'id' => 3,
            'name' => 'Filou'
        ]);

        $this->assertEquals(1, $event->persist());
        $this->assertEquals('created', file_get_contents(static::$cache_filename));
    }

    public function test_model_updated_event_is_emitted()
    {
        file_put_contents(static::$cache_filename, '');

        EventModelStub::updated(function ($model) {
            file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt', 'updated');
        });

        $pet = EventModelStub::connection("mysql")->where('id', 1)->first();
        if ($pet) {
            $pet->name = 'Loulou';
            $this->assertEquals(1, $pet->persist());
            $this->assertEquals('updated', file_get_contents(static::$cache_filename));
        } else {
            $this->markTestSkipped('No model found to update');
        }
    }

    public function test_model_deleted_event_is_emitted()
    {
        file_put_contents(static::$cache_filename, '');

        EventModelStub::deleted(function ($model) {
            file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt', 'deleted');
        });

        $pet = EventModelStub::connection("mysql")->where('id', 2)->first();
        if ($pet) {
            $this->assertEquals(1, $pet->delete());
            $this->assertEquals('deleted', file_get_contents(static::$cache_filename));
        } else {
            $this->markTestSkipped('No model found to delete');
        }
    }
}
