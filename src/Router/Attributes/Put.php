<?php

declare(strict_types=1);

namespace Bow\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Put extends Route
{
    /**
     * @param string $path
     * @param array $middleware
     * @param array $where
     * @param string|null $name
     */
    public function __construct(
        string $path,
        array $middleware = [],
        array $where = [],
        ?string $name = null
    ) {
        parent::__construct($path, ['PUT'], $middleware, $where, $name);
    }
}

