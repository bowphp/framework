<?php

declare(strict_types=1);

namespace Bow\Tests\Routing\Stubs;

use Bow\Router\Attributes\Controller;
use Bow\Router\Attributes\Get;

#[Controller(prefix: '/child')]
class ChildControllerStub extends ParentControllerStub
{
    #[Get('/own')]
    public function own(): void
    {
    }
}
