<?php

namespace Bow\Tests\Events;

use Bow\Database\Database;
use Bow\Event\Event;
use Bow\Tests\Events\EventModelStub;

class EventTest extends \PHPUnit\Framework\TestCase
{
    private string $cache_filename;

    public static function setUpBeforeClass(): void
    {
        \Bow\Container\Action::configure([], []);
        Database::statement('create table if not exists pets (id int primary key, name varchar(255))');
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement('drop table if exists pets');
    }

    public function setUp(): void
    {
        $this->cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';
    }

    public function test_event_binding_and_email()
    {
        Event::on('user.destroy', function (string $name) {
            $this->assertEquals($name, 'destroy');
        });

        Event::on('user.created', function (string $name) {
            $this->assertEquals($name, 'created');
        });

        Event::emit('user.created', 'created');
        Event::emit('user.destroy', 'destroy');
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
        $this->assertEquals('created', file_get_contents($this->cache_filename));
    }

    public function test_model_updated_event_emited()
    {
        $pet = EventModelStub::find(1);
        $pet->name = 'Loulou';

        $this->assertEquals($pet->save(), 1);
        $this->assertEquals('updated', file_get_contents($this->cache_filename));
    }

    public function test_model_deleted_event_emited()
    {
        $pet = EventModelStub::find(1);

        $this->assertEquals($pet->delete(), 1);
        $this->assertEquals('deleted', file_get_contents($this->cache_filename));
    }
}
