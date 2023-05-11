<?php

namespace Bow\Tests\Routing;

use Bow\Router\Route;
use Bow\Tests\Config\TestingConfiguration;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        $config->boot();
    }

    public function test_route_instance()
    {
        $route = new Route('/', function () {
            return 'hello';
        });

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_sample_uri()
    {
        $route = new Route('/', function () {
            return 'hello';
        });

        $this->assertTrue($route->match('/'));
        $this->assertEquals($route->call(), 'hello');
    }

    public function test_uri_with_one_parameter()
    {
        $route = new Route('/:name', function ($name) {
            return $name == 'bow';
        });

        $this->assertTrue($route->match('/bow'));
        $this->assertTrue($route->call());
        $this->assertTrue($route->match('/dakia'));

        $this->assertFalse($route->call());
        $this->assertFalse($route->match('/'));
    }

    public function test_uri_with_multi_parameter()
    {
        $route = new Route('/:name/:id', function ($name, $id) {
            return $name == 'bow' && $id == 1;
        });

        $this->assertTrue($route->match('/bow/1'));
        $this->assertTrue($route->call());
        $this->assertTrue($route->match('/dakia/1'));
        $this->assertFalse($route->call());
        $this->assertFalse($route->match('/'));
    }

    public function test_uri_with_one_parameter_and_constraint()
    {
        $route = new Route('/:name/:id', function ($name, $id) {
            return $name == 'bow' && $id == 1;
        });

        $route->where(['name' => '[a-z0-9_-]+', 'id' => '\d+']);

        $this->assertFalse($route->match('/bow/framework'));
        $this->assertFalse($route->match('/'));

        $this->assertTrue($route->match('/bow/1'));
        $this->assertTrue($route->call());

        $this->assertTrue($route->match('/bow/2'));
        $this->assertFalse($route->call());
    }

    public function test_uri_with_optionnal_parameter()
    {
        $route = new Route('/hello/:name?', function ($name = null) {
            if ($name) {
                return 'hello ' . $name;
            }
            return "hello world";
        });

        $this->assertFalse($route->match('/'));
        $this->assertTrue($route->match('/hello'));
        $this->assertEquals($route->call(), "hello world");
        $this->assertTrue($route->match('/hello/bow'));
        $this->assertEquals($route->call(), "hello bow");
    }
}
