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

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Database::connection("mysql");
        Database::connection("mysql")->statement('drop table if exists events');
        Database::connection("mysql")->statement('create table if not exists events (id int primary key, name varchar(255))');
        Database::connection("mysql")->statement("insert into events values (1, 'fluffy'), (2, 'dolly')");
        static::$cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

        Event::on(UserEventStub::class, UserEventListenerStub::class);
        Event::on('user.destroy', function (string $name) {
            Assert::assertEquals($name, 'destroy');
        });
        Event::on('user.created', function (string $name) {
            Assert::assertEquals($name, 'created');
        });
        Event::emit('user.created', 'created');
        Event::emit('user.destroy', 'destroy');
    }

    public function test_event_binding_and_email()
    {
        $this->assertTrue(Event::bound('user.destroy'));
        $this->assertTrue(Event::bound('user.created'));
        $this->assertTrue(Event::bound(UserEventStub::class));
        $this->assertFalse(Event::bound('user.updated'));
    }

    public function test_model_created_event_emited()
    {
        $event = EventModelStub::connection("mysql");
        $event->setAttributes([
            'id' => 3,
            'name' => 'Filou'
        ]);
        $this->assertEquals($event->save(), 1);
        $this->assertEquals('created', file_get_contents(static::$cache_filename));
    }

    public function test_model_updated_event_emited()
    {
        $pet = EventModelStub::connection("mysql")->first();
        $pet->name = 'Loulou';
        $this->assertEquals($pet->save(), 1);
        $this->assertEquals('updated', file_get_contents(static::$cache_filename));
    }

    public function test_model_deleted_event_emited()
    {
        $pet = EventModelStub::connection("mysql")->first();

        $this->assertEquals($pet->delete(), 1);
        $this->assertEquals('deleted', file_get_contents(static::$cache_filename));
    }

    public function test_directly_from_event()
    {
        UserEventStub::dispatch("papac");

        $this->assertEquals("papac", file_get_contents(static::$cache_filename));
    }
}
