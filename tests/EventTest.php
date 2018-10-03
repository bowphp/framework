<?php

use Bow\Event\Event;

class EventTable extends \Bow\Database\Barry\Model
{
    protected $table = 'pets';

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        file_put_contents(__DIR__.'/data/cache/event.txt', '');

        EventTable::created(function () {
            file_put_contents(__DIR__.'/data/cache/event.txt', 'created', FILE_APPEND);
        });

        EventTable::deleted(function () {
            file_put_contents(__DIR__.'/data/cache/event.txt', 'deleted', FILE_APPEND);
        });

        EventTable::updated(function () {
            file_put_contents(__DIR__.'/data/cache/event.txt', 'updated', FILE_APPEND);
        });
    }
}

class EventTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        statement('create table if not exists pets (id int, name varchar(255));');
    }

    public function testAddEvent()
    {
        Event::on('user.destroy', function ($name) {
            $this->assertEquals($name, 'destroy');
        });

        Event::on('user.created', function ($name) {
            $this->assertEquals($name, 'created');
        });
    }

    public function testEventEmit1()
    {
        Event::emit('user.created', 'created');
        Event::emit('user.destroy', 'destroy');
    }

    public function testModelCreated()
    {
        $pets = new EventTable();

        $this->assertInstanceOf(EventTable::class, $pets);

        $pets->setAttributes([
            'id' => 1,
            'name' => 'Filou'
        ]);

        $this->assertEquals($pets->save(), 1);
    }

    public function testModelUpdated()
    {
        $pet = EventTable::find(1);

        $this->assertInstanceOf(EventTable::class, $pet);

        $pet->name = 'Loulou';

        $this->assertEquals($pet->save(), 1);
    }

    public function testModelDeleted()
    {
        $pet = EventTable::find(1);

        $this->assertInstanceOf(EventTable::class, $pet);

        $this->assertEquals($pet->delete(), 1);
    }
}
