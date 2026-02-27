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


    public function test_route_matches_domain_and_path()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain('sub.example.com');
        $this->assertTrue($route->match('/foo/bar', 'sub.example.com'));
    }

    public function test_route_does_not_match_wrong_domain()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain('sub.example.com');
        $this->assertFalse($route->match('/foo/bar', 'other.example.com'));
    }

    public function test_route_matches_wildcard_domain()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain('*.example.com');
        $this->assertTrue($route->match('/foo/bar', 'api.example.com'));
        $this->assertTrue($route->match('/foo/bar', 'www.example.com'));
        $this->assertFalse($route->match('/foo/bar', 'example.com'));
    }

    public function test_route_matches_without_domain_constraint()
    {
        $route = new Route('/foo/bar', fn() => 'ok');
        $this->assertTrue($route->match('/foo/bar', 'any.domain.com'));
    }

    public function test_route_does_not_match_if_path_wrong_even_if_domain_matches()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain('sub.example.com');
        $this->assertFalse($route->match('/foo/other', 'sub.example.com'));
    }

    public function test_route_captures_subdomain_parameter()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain(':sub.example.com');
        $this->assertTrue($route->match('/foo/bar', 'app.example.com'));
        $this->assertEquals('app', $route->getParameter('sub'));
    }

    public function test_route_captures_multiple_domain_parameters()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain(':sub.:env.example.com');
        $this->assertTrue($route->match('/foo/bar', 'api.dev.example.com'));
        $this->assertEquals('api', $route->getParameter('sub'));
        $this->assertEquals('dev', $route->getParameter('env'));
    }

    public function test_route_does_not_match_if_domain_parameter_wrong()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain(':sub.example.com');
        $this->assertFalse($route->match('/foo/bar', 'example.com'));
        $this->assertNull($route->getParameter('sub'));
    }

    public function test_route_domain_parameter_with_wildcard()
    {
        $route = (new Route('/foo/bar', fn() => 'ok'))
            ->withDomain(':sub.*.example.com');
        $this->assertTrue($route->match('/foo/bar', 'app.api.example.com'));
        $this->assertEquals('app', $route->getParameter('sub'));
    }


    public function test_angle_bracket_param_in_path()
    {
        $route = new Route('/foo/<bar>', function ($bar) {
            return $bar;
        });
        $this->assertTrue($route->match('/foo/baz'));
        $this->assertEquals('baz', $route->call());
    }

    public function test_angle_bracket_multiple_params_in_path()
    {
        $route = new Route('/<foo>/<bar>', function ($foo, $bar) {
            return [$foo, $bar];
        });
        $this->assertTrue($route->match('/one/two'));
        $this->assertEquals(['one', 'two'], $route->call());
    }

    public function test_angle_bracket_optional_param_in_path()
    {
        $route = new Route('/foo/<bar?>', function ($bar = null) {
            return $bar ?? 'none';
        });
        $this->assertTrue($route->match('/foo'));
        $this->assertEquals('none', $route->call());
        $this->assertTrue($route->match('/foo/baz'));
        $this->assertEquals('baz', $route->call());
    }

    public function test_angle_bracket_param_in_domain()
    {
        $route = (new Route('/foo', fn() => 'ok'))
            ->withDomain('<sub>.example.com');
        $this->assertTrue($route->match('/foo', 'app.example.com'));
        $this->assertEquals('app', $route->getParameter('sub'));
    }

    public function test_angle_bracket_multiple_params_in_domain()
    {
        $route = (new Route('/foo', fn() => 'ok'))
            ->withDomain('<sub>.<env>.example.com');
        $this->assertTrue($route->match('/foo', 'api.dev.example.com'));
        $this->assertEquals('api', $route->getParameter('sub'));
        $this->assertEquals('dev', $route->getParameter('env'));
    }

    public function test_angle_bracket_param_with_wildcard_in_domain()
    {
        $route = (new Route('/foo', fn() => 'ok'))
            ->withDomain('<sub>.*.example.com');
        $this->assertTrue($route->match('/foo', 'app.api.example.com'));
        $this->assertEquals('app', $route->getParameter('sub'));
    }
}
