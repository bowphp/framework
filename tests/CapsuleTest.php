<?php

use Bow\Support\Capsule;

class MyClass {
    private $collection;

    public function __construct(\Bow\Support\Collection $collection)
    {
        $this->collection = $collection;
    }

    public function getCollection()
    {
        return $this->collection;
    }
}

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
        self::$capsule->bind('MyClass', function ($c) {
            fwrite(STDOUT, uniqid().'-');
            return new MyClass($c['\Bow\Support\Collection']);
        });
        self::$capsule->factory('\Bow\Support\Collection', function () {
            fwrite(STDOUT, uniqid().'-');
            return new \Bow\Support\Collection();
        });
    }

    public function testMakeContainer()
    {
        $this->assertInstanceOf(\stdClass::class, self::$capsule->make('\stdClass'));
    }

    public function testMakeCollectionContainer()
    {
        $this->assertNotInstanceOf(\stdClass::class, self::$capsule->make('\Bow\Support\Collection'));
        $this->assertInstanceOf(\Bow\Support\Collection::class, self::$capsule->make('\Bow\Support\Collection'));
    }

    public function testMakeMyClassContainer()
    {
        $this->assertInstanceOf(MyClass::class, self::$capsule->make('MyClass'));
    }
}