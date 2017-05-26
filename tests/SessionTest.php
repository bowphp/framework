<?php

if (getenv('DB_USER') == 'travis') {
    return;
}

use Bow\Session\Session;

class SessionTest extends \PHPUnit\Framework\TestCase
{
    public function testStart()
    {
        Session::add('name', 'bow');
    }

    public function testGet()
    {
        $name = Session::get('name');
        $this->assertEquals($name, 'bow');
    }

    public function testSet()
    {
        Session::set('name', 'papac');
        $name = Session::get('name');
        $this->assertEquals($name, 'papac');
    }

    public function testClear()
    {
        Session::set('name', 'papac');
        Session::clear();
        $name = Session::get('name');
        $this->assertNull($name);
    }

    public function testAddFlash()
    {
        $r = Session::flash('error', 'flash info');
        $this->assertTrue($r);
    }

    public function testGetFlash()
    {
        $flash = Session::get('error');
        $this->assertEquals($flash, 'flash info');
    }

    public function testClearFlash()
    {
        Session::clearFash();
        $flash = Session::get('error');
        $this->assertNotEquals($flash, 'flash info');
    }

    public function testHas()
    {
        $this->assertNotTrue(Session::has('error'));
    }
}