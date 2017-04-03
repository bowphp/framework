<?php

use \Bow\Support\DateAccess;

class TestDateAccess extends \PHPUnit\Framework\TestCase
{
    public function testGetInstance()
    {
        $instance = new DateAccess();
        $this->assertInstanceOf(DateAccess::class, $instance);
        return $instance;
    }

    /**
     * @param DateAccess $date
     * @depends testGetInstance
     */
    public function testIsFuture(DateAccess $date)
    {
        $this->assertEquals($date->isFuture(), !true);
    }

    /**
     * @param DateAccess $date
     * @depends testGetInstance
     */
    public function testIsPasse(DateAccess $date)
    {
        $this->assertEquals($date->isPassed(), true);
    }

    /**
     * @param DateAccess $date
     * @depends testGetInstance
     */
    public function testGetDate(DateAccess $date)
    {
        $this->assertEquals(is_int($date->getDay()), !true);
    }

    /**
     * @param DateAccess $date
     * @depends testGetInstance
     */
    public function testGetYear(DateAccess $date)
    {
        $this->assertEquals($date->getYear(), date('y'));
    }

    /**
     * @param DateAccess $date
     * @depends testGetInstance
     */
    public function testGetFullYear(DateAccess $date)
    {
        $this->assertEquals($date->getFullYear(), date('Y'));
    }
}