<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Route as RouteAttribute;
use ReflectionClass;
use ReflectionMethod;

class AttributeRouteRegistrar
{
    /**
     * The router instance
     *
     * @var Router
     */
    private Router $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes from controller classes
     *
     * @param string|array $controllers
     * @return void
     */
    public function register(string|array $controllers): void
    {
        $controllers = is_array($controllers) ? $controllers : [$controllers];

        foreach ($controllers as $controller) {
            $this->registerController($controller);
        }
    }

    /**
     * Register routes from controller
     *
     * @param string $controllerClass
     * @return void
     */
    private function registerController(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);

        // Get controller attribute
        $controllerAttributes = $reflection->getAttributes(Controller::class);
        $controllerAttribute = !empty($controllerAttributes) ? $controllerAttributes[0]->newInstance() : null;

        $prefix = $controllerAttribute?->getPrefix() ?? '';
        $controllerMiddleware = $controllerAttribute?->getMiddleware() ?? [];

        // Scan methods
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            // Get route attributes
            $routeAttributes = $method->getAttributes(
                RouteAttribute::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

            foreach ($routeAttributes as $attribute) {
                /** @var RouteAttribute $routeAttr */
                $routeAttr = $attribute->newInstance();

                // Build path
                $routePath = $routeAttr->getPath();
                $routePath = '/' . ltrim($routePath, '/');
                $fullPath = $prefix !== '' ? rtrim($prefix, '/') . $routePath : $routePath;

                // Merge middleware
                $middleware = array_merge($controllerMiddleware, $routeAttr->getMiddleware());

                // Register route
                $route = $this->router->match(
                    $routeAttr->getMethods(),
                    $fullPath,
                    [$controllerClass, $method->getName()]
                );

                if (!empty($middleware)) {
                    $route->middleware($middleware);
                }

                if (!empty($routeAttr->getWhere())) {
                    $route->where($routeAttr->getWhere());
                }

                if ($routeAttr->getName() !== null) {
                    $route->name($routeAttr->getName());
                }
            }
        }
    }
}
