<?php

declare(strict_types=1);

namespace Bow\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    /**
     * @param string $prefix
     * @param array $middleware
     * @param string|null $name
     */
    public function __construct(
        public readonly string $prefix = '',
        public readonly array $middleware = [],
        public readonly ?string $name = null
    ) {}

    /**
     * Get the prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
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
     * Get the route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
