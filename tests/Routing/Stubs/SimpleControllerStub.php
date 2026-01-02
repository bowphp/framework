<?php

declare(strict_types=1);

namespace Bow\Tests\Routing\Stubs;

use Bow\Router\Attributes\Get;
use Bow\Router\Attributes\Post;

/**
 * Simple controller without Controller attribute
 */
class SimpleControllerStub
{
    #[Get('/simple')]
    public function index(): array
    {
        return ['action' => 'simple_index'];
    }

    #[Post('/simple', name: 'simple.store')]
    public function store(): array
    {
        return ['action' => 'simple_store'];
    }
}

