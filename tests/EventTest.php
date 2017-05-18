<?php

use Bow\Event\Event;

class EventTest extends \PHPUnit\Framework\TestCase
{
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
}