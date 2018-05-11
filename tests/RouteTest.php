<?php

use Bow\Http\Request;
use Bow\Application\Route;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance()
    {
        $route = new Route(
            '/', function () {
                return 'hello';
            }
        );

        $this->assertInstanceOf(Route::class, $route);
    }

    public function testSampleUri()
    {
        $route = new Route('/', function () {
            return 'hello';
        });

        $this->assertTrue($route->match('/'));
        $this->assertEquals($route->call(new Request()), 'hello');
    }

    public function testUriWithOneParameter()
    {
        $route = new Route('/:name', function ($name) {
            return $name == 'bow';
        });

        $this->assertTrue($route->match('/bow'));
        $this->assertTrue($route->call(new Request()));

        $this->assertTrue($route->match('/dakia'));
        $this->assertFalse($route->call(new Request()));

        $this->assertFalse($route->match('/'));
    }

    public function testUriWithMultiParameter()
    {
        $route = new Route('/:name/:id', function ($name, $id) {
            return $name == 'bow' && $id == 1;
        });

        $this->assertTrue($route->match('/bow/1'));
        $this->assertTrue($route->call(new Request()));

        $this->assertTrue($route->match('/dakia/1'));
        $this->assertFalse($route->call(new Request()));

        $this->assertFalse($route->match('/'));
    }

    public function testUriWithOneParameterAndConstraint()
    {
        $route = new Route('/:name/:id', function ($name, $id) {
            return $name == 'bow' && $id == 1;
        });

        $route->where(['name' => '[a-z0-9_-]+', 'id' => '\d+']);
        $this->assertTrue($route->match('/bow/1'));
        $this->assertTrue($route->call(new Request()));

        $route->where(['name' => '[a-z0-9_-]+', 'id' => '\d+']);
        $this->assertFalse($route->match('/bow/framework'));
        $this->assertFalse($route->match('/'));
    }
}