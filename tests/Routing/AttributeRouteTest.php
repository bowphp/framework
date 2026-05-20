<?php

declare(strict_types=1);

namespace Bow\Tests\Routing;

use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Delete;
use Bow\Router\Attributes\Get;
use Bow\Router\Attributes\Options;
use Bow\Router\Attributes\Patch;
use Bow\Router\Attributes\Post;
use Bow\Router\Attributes\Put;
use Bow\Router\Attributes\Route;
use Bow\Tests\Routing\Stubs\UserControllerStub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for Router Attributes
 * These tests verify the attributes work correctly without needing the full framework
 *
 * @group unit
 */
class AttributeRouteTest extends TestCase
{
    // ===== HTTP Method Attribute Tests =====

    public function test_get_attribute_creates_correct_route(): void
    {
        $get = new Get('/users', middleware: ['auth'], where: ['id' => '[0-9]+'], name: 'users.index');

        $this->assertEquals('/users', $get->getPath());
        $this->assertEquals(['GET'], $get->getMethods());
        $this->assertEquals(['auth'], $get->getMiddleware());
        $this->assertEquals(['id' => '[0-9]+'], $get->getWhere());
        $this->assertEquals('users.index', $get->getName());
    }

    public function test_post_attribute_creates_correct_route(): void
    {
        $post = new Post('/users');

        $this->assertEquals('/users', $post->getPath());
        $this->assertEquals(['POST'], $post->getMethods());
    }

    public function test_put_attribute_creates_correct_route(): void
    {
        $put = new Put('/users/:id');

        $this->assertEquals('/users/:id', $put->getPath());
        $this->assertEquals(['PUT'], $put->getMethods());
    }

    public function test_delete_attribute_creates_correct_route(): void
    {
        $delete = new Delete('/users/:id');

        $this->assertEquals('/users/:id', $delete->getPath());
        $this->assertEquals(['DELETE'], $delete->getMethods());
    }

    public function test_patch_attribute_creates_correct_route(): void
    {
        $patch = new Patch('/users/:id');

        $this->assertEquals('/users/:id', $patch->getPath());
        $this->assertEquals(['PATCH'], $patch->getMethods());
    }

    public function test_options_attribute_creates_correct_route(): void
    {
        $options = new Options('/users');

        $this->assertEquals('/users', $options->getPath());
        $this->assertEquals(['OPTIONS'], $options->getMethods());
    }

    public function test_route_attribute_with_multiple_methods(): void
    {
        $route = new Route('/users', methods: ['GET', 'post', 'PUT']);

        $this->assertEquals('/users', $route->getPath());
        $this->assertEquals(['GET', 'POST', 'PUT'], $route->getMethods());
    }

    // ===== Controller Attribute Tests =====

    public function test_controller_attribute_with_prefix_and_middleware(): void
    {
        $controller = new Controller(prefix: '/api/v1', middleware: ['auth', 'throttle'], name: 'api');

        $this->assertEquals('/api/v1', $controller->getPrefix());
        $this->assertEquals(['auth', 'throttle'], $controller->getMiddleware());
        $this->assertEquals('api', $controller->getName());
    }

    public function test_controller_attribute_defaults(): void
    {
        $controller = new Controller();

        $this->assertEquals('', $controller->getPrefix());
        $this->assertEquals([], $controller->getMiddleware());
        $this->assertNull($controller->getName());
    }

    // ===== Reflection Tests =====

    public function test_user_controller_has_controller_attribute(): void
    {
        $reflection = new ReflectionClass(UserControllerStub::class);
        $attributes = $reflection->getAttributes(Controller::class);

        $this->assertCount(1, $attributes);

        /** @var Controller $controller */
        $controller = $attributes[0]->newInstance();

        $this->assertEquals('/api/users', $controller->getPrefix());
        $this->assertEquals(['auth'], $controller->getMiddleware());
    }

    public function test_user_controller_methods_have_route_attributes(): void
    {
        $reflection = new ReflectionClass(UserControllerStub::class);

        // Test index method
        $indexMethod = $reflection->getMethod('index');
        $indexAttributes = $indexMethod->getAttributes(Get::class);
        $this->assertCount(1, $indexAttributes);

        /** @var Get $getAttr */
        $getAttr = $indexAttributes[0]->newInstance();
        $this->assertEquals('/', $getAttr->getPath());

        // Test store method
        $storeMethod = $reflection->getMethod('store');
        $storeAttributes = $storeMethod->getAttributes(Post::class);
        $this->assertCount(1, $storeAttributes);

        /** @var Post $postAttr */
        $postAttr = $storeAttributes[0]->newInstance();
        $this->assertEquals('/', $postAttr->getPath());
        $this->assertEquals(['validate'], $postAttr->getMiddleware());
    }

    public function test_can_get_all_route_attributes_using_instanceof(): void
    {
        $reflection = new ReflectionClass(UserControllerStub::class);
        $indexMethod = $reflection->getMethod('index');

        // Get all Route attributes (including subclasses like Get, Post, etc.)
        $routeAttributes = $indexMethod->getAttributes(
            Route::class,
            \ReflectionAttribute::IS_INSTANCEOF
        );

        $this->assertCount(1, $routeAttributes);
    }

    public function test_route_attribute_middleware_merges_correctly(): void
    {
        $route = new Get('/test', middleware: ['first', 'second']);

        $this->assertEquals(['first', 'second'], $route->getMiddleware());
    }

    public function test_route_attribute_where_constraints(): void
    {
        $route = new Get('/users/:id/:slug', where: ['id' => '[0-9]+', 'slug' => '[a-z-]+']);

        $this->assertEquals([
            'id' => '[0-9]+',
            'slug' => '[a-z-]+'
        ], $route->getWhere());
    }

    public function test_all_http_attributes_extend_route(): void
    {
        $this->assertInstanceOf(Route::class, new Get('/'));
        $this->assertInstanceOf(Route::class, new Post('/'));
        $this->assertInstanceOf(Route::class, new Put('/'));
        $this->assertInstanceOf(Route::class, new Delete('/'));
        $this->assertInstanceOf(Route::class, new Patch('/'));
        $this->assertInstanceOf(Route::class, new Options('/'));
    }
}
