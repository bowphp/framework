<?php

declare(strict_types=1);

namespace Bow\Tests\Routing\Stubs;

use Bow\Router\Attributes\Get;

class ParentControllerStub
{
    #[Get('/inherited')]
    public function inherited(): void
    {
    }
}
