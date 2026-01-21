<?php

namespace Bow\Tests\Container;

use Bow\Container\Capsule;
use Bow\Tests\Container\Stubs\FileLogger;
use Bow\Tests\Container\Stubs\LoggerInterface;
use Bow\Tests\Container\Stubs\MyClass;
use Bow\Tests\Container\Stubs\OrderService;
use Bow\Tests\Container\Stubs\PaymentGatewayInterface;
use Bow\Tests\Container\Stubs\PaypalPaymentGateway;
use Bow\Tests\Container\Stubs\SimpleService;
use Bow\Tests\Container\Stubs\StripePaymentGateway;
use StdClass;

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
        static::$capsule->bind('std-class', fn() => new StdClass());
        static::$capsule->bind('my-class', fn(Capsule $container) => new MyClass($container['\Bow\Support\Collection']));
        static::$capsule->instance("my-class-instance", new MyClass(new \Bow\Support\Collection()));
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

    public function test_bind_interface_to_concrete_implementation()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway());

        $gateway = $capsule->make(PaymentGatewayInterface::class);

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(StripePaymentGateway::class, $gateway);
        $this->assertEquals('stripe', $gateway->getName());
    }

    public function test_bind_interface_to_different_implementation()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new PaypalPaymentGateway());

        $gateway = $capsule->make(PaymentGatewayInterface::class);

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(PaypalPaymentGateway::class, $gateway);
        $this->assertEquals('paypal', $gateway->getName());
    }

    public function test_bind_multiple_interfaces()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway());
        $capsule->bind(LoggerInterface::class, fn() => new FileLogger());

        $gateway = $capsule->make(PaymentGatewayInterface::class);
        $logger = $capsule->make(LoggerInterface::class);

        $this->assertInstanceOf(StripePaymentGateway::class, $gateway);
        $this->assertInstanceOf(FileLogger::class, $logger);
    }

    public function test_auto_resolve_dependencies_with_interfaces()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway());
        $capsule->bind(LoggerInterface::class, fn() => new FileLogger());
        $capsule->bind(OrderService::class, fn(Capsule $c) => new OrderService(
            $c->make(PaymentGatewayInterface::class),
            $c->make(LoggerInterface::class)
        ));

        $orderService = $capsule->make(OrderService::class);

        $this->assertInstanceOf(OrderService::class, $orderService);
        $this->assertInstanceOf(StripePaymentGateway::class, $orderService->getPaymentGateway());
        $this->assertInstanceOf(FileLogger::class, $orderService->getLogger());
    }

    public function test_injected_service_is_functional()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway());
        $capsule->bind(LoggerInterface::class, fn() => new FileLogger());
        $capsule->bind(OrderService::class, fn(Capsule $c) => new OrderService(
            $c->make(PaymentGatewayInterface::class),
            $c->make(LoggerInterface::class)
        ));

        $orderService = $capsule->make(OrderService::class);
        $result = $orderService->processOrder(100.00);

        $this->assertTrue($result);
        $this->assertCount(1, $orderService->getLogger()->getMessages());
    }

    public function test_instance_returns_same_object()
    {
        $capsule = new Capsule();
        $logger = new FileLogger();
        $capsule->instance(LoggerInterface::class, $logger);

        $resolved1 = $capsule->make(LoggerInterface::class);
        $resolved2 = $capsule->make(LoggerInterface::class);

        $this->assertSame($resolved1, $resolved2);
        $this->assertSame($logger, $resolved1);
    }

    public function test_instance_preserves_state()
    {
        $capsule = new Capsule();
        $logger = new FileLogger();
        $capsule->instance(LoggerInterface::class, $logger);

        $resolved = $capsule->make(LoggerInterface::class);
        $resolved->log('First message');

        $resolvedAgain = $capsule->make(LoggerInterface::class);

        $this->assertCount(1, $resolvedAgain->getMessages());
        $this->assertEquals('[FILE] First message', $resolvedAgain->getMessages()[0]);
    }

    public function test_factory_creates_new_instance_each_time()
    {
        $capsule = new Capsule();
        $capsule->factory(LoggerInterface::class, fn() => new FileLogger());

        $logger1 = $capsule->make(LoggerInterface::class);
        $logger1->log('Message 1');

        $logger2 = $capsule->make(LoggerInterface::class);

        $this->assertNotSame($logger1, $logger2);
        $this->assertCount(1, $logger1->getMessages());
        $this->assertCount(0, $logger2->getMessages());
    }

    public function test_factory_with_container_injection()
    {
        $capsule = new Capsule();
        $capsule->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway());
        $capsule->factory('payment-processor', fn(Capsule $c) => $c->make(PaymentGatewayInterface::class));

        $processor = $capsule->make('payment-processor');

        $this->assertInstanceOf(StripePaymentGateway::class, $processor);
    }

    public function test_array_access_offset_exists()
    {
        $capsule = new Capsule();
        $capsule->bind('existing-key', fn() => new StdClass());

        $this->assertTrue(isset($capsule['existing-key']));
        $this->assertFalse(isset($capsule['non-existing-key']));
    }

    public function test_array_access_offset_get()
    {
        $capsule = new Capsule();
        $capsule->bind('test-key', fn() => new StripePaymentGateway());

        $result = $capsule['test-key'];

        $this->assertInstanceOf(StripePaymentGateway::class, $result);
    }

    public function test_array_access_offset_set()
    {
        $capsule = new Capsule();
        $capsule['custom-service'] = fn() => new FileLogger();

        $result = $capsule->make('custom-service');

        $this->assertInstanceOf(FileLogger::class, $result);
    }

    public function test_array_access_offset_unset()
    {
        $capsule = new Capsule();
        $capsule->bind('removable', fn() => new StdClass());

        $this->assertTrue(isset($capsule['removable']));

        unset($capsule['removable']);

        // After unset, the key still exists in cache but the register is removed
        // Attempting to resolve will try to instantiate "removable" as a class
        $this->expectException(\ReflectionException::class);
        $capsule->make('removable');
    }

    public function test_make_with_parameters()
    {
        $capsule = new Capsule();

        $service = $capsule->makeWith(SimpleService::class, ['custom-name']);

        $this->assertInstanceOf(SimpleService::class, $service);
        $this->assertEquals('custom-name', $service->getName());
    }

    public function test_make_with_default_parameters()
    {
        $capsule = new Capsule();

        $service = $capsule->make(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $service);
        $this->assertEquals('default', $service->getName());
    }

    public function test_bind_returns_capsule_for_chaining()
    {
        $capsule = new Capsule();

        $result = $capsule
            ->bind(PaymentGatewayInterface::class, fn() => new StripePaymentGateway())
            ->bind(LoggerInterface::class, fn() => new FileLogger());

        $this->assertInstanceOf(Capsule::class, $result);
    }

    public function test_factory_returns_capsule_for_chaining()
    {
        $capsule = new Capsule();

        $result = $capsule
            ->factory('service1', fn() => new StdClass())
            ->factory('service2', fn() => new StdClass());

        $this->assertInstanceOf(Capsule::class, $result);
    }

    public function test_instance_returns_capsule_for_chaining()
    {
        $capsule = new Capsule();

        $result = $capsule
            ->instance('logger', new FileLogger())
            ->instance('gateway', new StripePaymentGateway());

        $this->assertInstanceOf(Capsule::class, $result);
    }

    public function test_get_instance_returns_singleton()
    {
        $instance1 = Capsule::getInstance();
        $instance2 = Capsule::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_bind_with_class_name_string()
    {
        $capsule = new Capsule();
        $capsule->bind('payment', StripePaymentGateway::class);

        $result = $capsule->make('payment');

        $this->assertInstanceOf(StripePaymentGateway::class, $result);
    }
}
