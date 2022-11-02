<?php

namespace Bow\Tests\Container;

use StdClass;
use Bow\Container\Capsule;
use Bow\Tests\Stubs\Container\MyClass;

class CapsuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Capsule
     */
    protected static Capsule $capsule;

    public static function setUpBeforeClass(): void
    {
        static::$capsule = new Capsule();
        static::$capsule->factory('\Bow\Support\Collection', fn() => new \Bow\Support\Collection());
        static::$capsule->bind('std-class', fn () => new StdClass());
        static::$capsule->bind('my-class', fn (Capsule $container) => new MyClass($container['\Bow\Support\Collection']));
        static::$capsule->instance("my-class-instance", new MyClass(new \Bow\Support\Collection));
    }

    public function test_make_simple_class_instance_from_container()
    {
        $this->assertInstanceOf(StdClass::class, static::$capsule->make('std-class'));
    }

    public function test_factory()
    {
        $this->assertNotInstanceOf(StdClass::class, static::$capsule->make('\Bow\Support\Collection'));
        $this->assertInstanceOf(\Bow\Support\Collection::class, static::$capsule->make('\Bow\Support\Collection'));
    }

    public function test_make_my_class_container()
    {
        $my_class = static::$capsule->make('my-class');

        $this->assertInstanceOf(MyClass::class, $my_class);
        $this->assertInstanceOf(\Bow\Support\Collection::class, $my_class->getCollection());
    }
}
