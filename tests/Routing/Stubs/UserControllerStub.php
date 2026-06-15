<?php

declare(strict_types=1);

namespace Bow\Tests\Routing\Stubs;

use Bow\Http\Request;
use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Delete;
use Bow\Router\Attributes\Get;
use Bow\Router\Attributes\Patch;
use Bow\Router\Attributes\Post;
use Bow\Router\Attributes\Put;

/**
 * Test controller with route attributes
 */
#[Controller(prefix: '/api/users', middleware: ['auth'])]
class UserControllerStub
{
    #[Get('/')]
    public function index(): array
    {
        return ['action' => 'index'];
    }

    #[Get('/:id', where: ['id' => '[0-9]+'])]
    public function show(Request $request): array
    {
        return ['action' => 'show', 'id' => $request->get('id')];
    }

    #[Post('/', middleware: ['validate'])]
    public function store(Request $request): array
    {
        return ['action' => 'store'];
    }

    #[Put('/:id')]
    public function update(Request $request): array
    {
        return ['action' => 'update', 'id' => $request->get('id')];
    }

    #[Patch('/:id')]
    public function patch(Request $request): array
    {
        return ['action' => 'patch', 'id' => $request->get('id')];
    }

    #[Delete('/:id', middleware: ['admin'])]
    public function destroy(Request $request): array
    {
        return ['action' => 'destroy', 'id' => $request->get('id')];
    }
}

