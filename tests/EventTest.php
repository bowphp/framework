<?php

use Bow\Event\Event;

class EventTable extends \Bow\Database\Barry\Model
{
    protected $table = 'pets';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        EventTable::created(function() {
            // fwrite(STDOUT, 'Created');
        });
        EventTable::deleted(function() {
            // fwrite(STDOUT, 'Deleted');
        });
        EventTable::updated(function() {
            // fwrite(STDOUT, 'Updated');
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
        Event::on('user.destroy', function($name) {
           $this->assertEquals($name, 'destroy');
        });

        Event::on('user.created', function($name) {
           $this->assertEquals($name, 'created');
        });
    }

    public function testEventEmit1()
    {
        Event::emit('user.created', 'created');
    }
    
    public function testEventEmit2()
    {
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
        $pets = EventTable::find(1);
        $this->assertInstanceOf(EventTable::class, $pets);
        $pets->name = 'Loulou';
        $this->assertEquals($pets->save(), 1);
    }

    public function testModelDeleted()
    {
        $pets = EventTable::find(1);
        $this->assertInstanceOf(EventTable::class, $pets);
        $this->assertEquals($pets->delete(), 1);
    }
}