<?php

declare(strict_types=1);

namespace Bow\Tests\Routing\Stubs;

use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Get;

#[Controller(prefix: '/api/users', name: 'users.')]
class NamedUserControllerStub
{
    #[Get('/', name: 'index')]
    public function index(): void
    {
    }

    #[Get('/:id', name: 'show')]
    public function show(): void
    {
    }
}
