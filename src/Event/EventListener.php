<?php

namespace Bow\Event;

use Bow\Container\Action;
use Bow\Session\Session;
use Bow\Support\Collection;

abstract class EventListener
{
    abstract public function process(array $payload);
}
