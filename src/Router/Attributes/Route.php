<?php

declare(strict_types=1);

namespace Bow\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @param string $path
     * @param array $methods
     * @param array $middleware
     * @param array $where
     * @param string|null $name
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods = ['GET'],
        public readonly array $middleware = [],
        public readonly array $where = [],
        public readonly ?string $name = null
    ) {
    }

    /**
     * Get the route path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the http methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return array_map('strtoupper', $this->methods);
    }

    /**
     * Get the middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the route constraints
     *
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Get the route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

