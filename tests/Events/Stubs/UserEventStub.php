<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Event\Contracts\AppEvent;
use Bow\Event\Dispatchable;

class UserEventStub implements AppEvent
{
    use Dispatchable;

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
