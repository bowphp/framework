<?php

namespace Bow\Event;

abstract class EventListener
{
    /**
     * Process the event
     *
     * @param array $payload
     * @return mixed
     */
    abstract public function process(array $payload);
}
