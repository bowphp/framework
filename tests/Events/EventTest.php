<?php

namespace Bow\Tests\Events;

use Bow\Event\Event;
use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\Assert;
use Bow\Tests\Events\Stubs\EventModelStub;
use Bow\Tests\Events\Stubs\UserEventStub;
use Bow\Tests\Events\Stubs\UserEventListenerStub;

class EventTest extends \PHPUnit\Framework\TestCase
{
    private static string $cache_filename;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Database::statement('create table if not exists events (id int primary key, name varchar(255))');
        Database::statement("insert into events values (1, 'fluffy'), (2, 'dolly')");
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

    public static function tearDownAfterClass(): void
    {
        Database::statement('drop table if exists events');
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
        $pet_model = new EventModelStub();
        $pet_model->truncate();
        $pet_model->setAttributes([
            'id' => 1,
            'name' => 'Filou'
        ]);
        $this->assertEquals($pet_model->save(), 1);
        $this->assertEquals('created', file_get_contents(static::$cache_filename));
    }

    public function test_model_updated_event_emited()
    {
        $pet = EventModelStub::find(1);
        $pet->name = 'Loulou';
        $this->assertEquals($pet->save(), 1);
        $this->assertEquals('updated', file_get_contents(static::$cache_filename));
    }

    public function test_model_deleted_event_emited()
    {
        $pet = EventModelStub::find(1);

        $this->assertEquals($pet->delete(), 1);
        $this->assertEquals('deleted', file_get_contents(static::$cache_filename));
    }

    public function test_directly_from_event()
    {
        UserEventStub::dispatch("papac");

        $this->assertEquals("papac", file_get_contents(static::$cache_filename));
    }
}
