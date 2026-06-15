<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Route as RouteAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class AttributeRouteRegistrar
{
    /**
     * @param Router $router
     */
    public function __construct(private readonly Router $router)
    {
    }

    /**
     * Register routes from one or many controller classes.
     *
     * @param class-string|list<class-string> $controllers
     */
    public function register(string|array $controllers): void
    {
        foreach ((array) $controllers as $controllerClass) {
            $this->registerController($controllerClass);
        }
    }

    /**
     * Scan a single controller class and register all of its attribute routes.
     */
    private function registerController(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);
        $controllerAttribute = $this->resolveControllerAttribute($reflection);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldSkipMethod($method, $reflection)) {
                continue;
            }

            $this->registerMethodRoutes($method, $controllerClass, $controllerAttribute);
        }
    }

    /**
     * Resolve the `#[Controller]` attribute on the class, if present. Accepts
     * subclasses of `Controller` via IS_INSTANCEOF.
     */
    private function resolveControllerAttribute(ReflectionClass $reflection): ?Controller
    {
        $attributes = $reflection->getAttributes(Controller::class, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes !== [] ? $attributes[0]->newInstance() : null;
    }

    /**
     * Skip magic methods and methods inherited from parent classes (those
     * belong to whichever parent declared them, not this controller).
     */
    private function shouldSkipMethod(ReflectionMethod $method, ReflectionClass $reflection): bool
    {
        if (str_starts_with($method->getName(), '__')) {
            return true;
        }

        return $method->getDeclaringClass()->getName() !== $reflection->getName();
    }

    /**
     * Register every `#[Route]`-derived attribute on a single controller method.
     */
    private function registerMethodRoutes(
        ReflectionMethod $method,
        string $controllerClass,
        ?Controller $controllerAttribute,
    ): void {
        $routeAttributes = $method->getAttributes(
            RouteAttribute::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );

        foreach ($routeAttributes as $attribute) {
            /** @var RouteAttribute $routeAttr */
            $routeAttr = $attribute->newInstance();

            $route = $this->router->match(
                $routeAttr->getMethods(),
                $this->composePath($controllerAttribute, $routeAttr->getPath()),
                [$controllerClass, $method->getName()],
            );

            $this->applyRouteOptions($route, $routeAttr, $controllerAttribute);
        }
    }

    /**
     * Prepend the controller-level prefix to the route path, normalising
     * leading/trailing slashes.
     */
    private function composePath(?Controller $controllerAttribute, string $routePath): string
    {
        $routePath = '/' . ltrim($routePath, '/');
        $prefix = $controllerAttribute?->getPrefix() ?? '';

        return $prefix !== '' ? rtrim($prefix, '/') . $routePath : $routePath;
    }

    /**
     * Apply middleware, parameter constraints, and route name from both the
     * controller-level and route-level attributes. The controller's name
     * acts as a prefix and is concatenated verbatim — callers control the
     * separator (e.g. `name: 'users.'` + `name: 'index'` => `users.index`).
     */
    private function applyRouteOptions(
        Route $route,
        RouteAttribute $routeAttr,
        ?Controller $controllerAttribute,
    ): void {
        $middleware = array_merge(
            $controllerAttribute?->getMiddleware() ?? [],
            $routeAttr->getMiddleware(),
        );

        if ($middleware !== []) {
            $route->middleware($middleware);
        }

        if ($routeAttr->getWhere() !== []) {
            $route->where($routeAttr->getWhere());
        }

        if ($routeAttr->getName() !== null) {
            $namePrefix = $controllerAttribute?->getName() ?? '';
            $route->name($namePrefix . $routeAttr->getName());
        }
    }
}
