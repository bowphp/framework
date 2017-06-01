<?php

use Bow\Support\Capsule;

class CapsuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Capsule
     */
    protected static $capsule;

    public static function setUpBeforeClass()
    {
        self::$capsule = new Capsule();
    }

    public function testAddContainer()
    {
        $this->assertInstanceOf(Capsule::class, self::$capsule);
        self::$capsule->bind('\stdClass', function () {
            return new \stdClass();
        });
    }

    public function testMakeContainer()
    {
        $this->assertInstanceOf(\stdClass::class, self::$capsule->make('\stdClass'));
    }
}