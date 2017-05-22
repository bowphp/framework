<?php

use Bow\Session\Session;

class SessionText extends \PHPUnit\Framework\TestCase
{
    public function testStart()
    {
        Session::add('name', 'bow');
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testGet()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $name = Session::get('name');
        $this->assertEquals($name, 'bow');
    }

    public function testSet()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        Session::set('name', 'papac');
        $name = Session::get('name');
        $this->assertEquals($name, 'papac');
    }

    public function testClear()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        Session::set('name', 'papac');
        Session::clear();
        $name = Session::get('name');
        $this->assertNull($name);
    }

    public function testAddFlash()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $r = Session::flash('error', 'flash info');
        $this->assertTrue($r);
    }

    public function testGetFlash()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $flash = Session::get('error');
        $this->assertEquals($flash, 'flash info');
    }

    public function testClearFlash()
    {
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        Session::clearFash();
        $flash = Session::get('error');
        $this->assertEquals($flash, 'flash info');
    }

    public function testHas()
    {
        $this->assertNotTrue(Session::has('error'));
    }
}