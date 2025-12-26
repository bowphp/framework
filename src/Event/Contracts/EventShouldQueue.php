<?php

namespace Bow\Event\Contracts;

interface EventShouldQueue
{
    public function setQueue(string $queue): void;
}
