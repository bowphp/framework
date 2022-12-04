<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Event\Contracts\ApplicationEvent;

class UserCreatedEvent extends ApplicationEvent
{
    public string $name = "UserCreatedEvent";

    public function construct(string $name)
    {
        $this->name = $name;
    }
}