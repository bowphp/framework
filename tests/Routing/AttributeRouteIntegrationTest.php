<?php

declare(strict_types=1);

namespace Bow\Tests\Routing;

use Bow\Router\AttributeRouteRegistrar;
use Bow\Router\Router;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Routing\Stubs\SimpleControllerStub;
use Bow\Tests\Routing\Stubs\UserControllerStub;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Router Attribute Registration
 * These tests require the full framework configuration
 *
 * @group integration
 */
class AttributeRouteIntegrationTest extends TestCase
{
    private Router $router;
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            // Ensure the testing constant is defined
            if (!defined('TESTING_RESOURCE_BASE_DIRECTORY')) {
                define('TESTING_RESOURCE_BASE_DIRECTORY', sprintf('%s/bowphp_testing', sys_get_temp_dir()));
            }

            TestingConfiguration::getConfig();
            static::$configured = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the router for each test
        $this->router = Router::configure();
    }

    public function test_registrar_registers_routes_from_controller(): void
    {
        $registrar = new AttributeRouteRegistrar($this->router);
        $registrar->register(UserControllerStub::class);

        $routes = $this->router->getRoutes();

        // Check that routes were registered
        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('POST', $routes);
        $this->assertArrayHasKey('PUT', $routes);
        $this->assertArrayHasKey('DELETE', $routes);
        $this->assertArrayHasKey('PATCH', $routes);
    }

    public function test_registrar_registers_routes_with_correct_paths(): void
    {
        $registrar = new AttributeRouteRegistrar($this->router);
        $registrar->register(UserControllerStub::class);

        $routes = $this->router->getRoutes();

        // Get the registered GET routes
        $getRoutes = $routes['GET'] ?? [];

        // Check that we have at least the expected routes
        $this->assertGreaterThanOrEqual(2, count($getRoutes));

        // Get paths from routes
        $paths = array_map(fn($route) => $route->getPath(), $getRoutes);

        // Check if the path starts with /api/users
        $hasIndexRoute = false;
        $hasShowRoute = false;
        foreach ($paths as $path) {
            if ($path === '/api/users/' || $path === '/api/users') {
                $hasIndexRoute = true;
            }
            if (str_contains($path, '/api/users/:id') || str_contains($path, '/api/users/')) {
                $hasShowRoute = true;
            }
        }
        $this->assertTrue($hasIndexRoute, 'Index route should be registered');
        $this->assertTrue($hasShowRoute, 'Show route should be registered');
    }

    public function test_registrar_handles_controller_without_controller_attribute(): void
    {
        $registrar = new AttributeRouteRegistrar($this->router);
        $registrar->register(SimpleControllerStub::class);

        $routes = $this->router->getRoutes();

        // Should still register routes
        $this->assertArrayHasKey('GET', $routes);
        $this->assertArrayHasKey('POST', $routes);
    }

    public function test_router_register_method_works(): void
    {
        $this->router->register(UserControllerStub::class);

        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('GET', $routes);
        $this->assertNotEmpty($routes['GET']);
    }

    public function test_router_register_accepts_array_of_controllers(): void
    {
        $this->router->register([
            UserControllerStub::class,
            SimpleControllerStub::class
        ]);

        $routes = $this->router->getRoutes();

        // Get all registered paths
        $allPaths = [];
        foreach ($routes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $allPaths[] = $route->getPath();
            }
        }

        // Check that routes from both controllers are registered
        $hasUserRoute = false;
        $hasSimpleRoute = false;
        foreach ($allPaths as $path) {
            if (str_starts_with($path, '/api/users')) {
                $hasUserRoute = true;
            }
            if (str_contains($path, '/simple')) {
                $hasSimpleRoute = true;
            }
        }
        $this->assertTrue($hasUserRoute, 'User controller routes should be registered');
        $this->assertTrue($hasSimpleRoute, 'Simple controller routes should be registered');
    }

    public function test_router_register_returns_router_for_chaining(): void
    {
        $result = $this->router->register(UserControllerStub::class);

        $this->assertInstanceOf(Router::class, $result);
    }

    public function test_route_middleware_is_applied_correctly(): void
    {
        $this->router->register(UserControllerStub::class);

        $routes = $this->router->getRoutes();
        $postRoutes = $routes['POST'] ?? [];

        // Find the store route
        $storeRoute = null;
        foreach ($postRoutes as $route) {
            if (str_contains($route->getPath(), '/api/users')) {
                $storeRoute = $route;
                break;
            }
        }

        $this->assertNotNull($storeRoute);

        // The action should contain middleware
        $action = $storeRoute->getAction();
        $this->assertIsArray($action);
        $this->assertArrayHasKey('middleware', $action);

        // Should have both controller and route middleware
        $middleware = $action['middleware'];
        $this->assertContains('auth', $middleware);
        $this->assertContains('validate', $middleware);
    }
}
