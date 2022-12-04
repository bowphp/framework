<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Event\Dispatchable;
use Bow\Event\Contracts\AppEvent;

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
