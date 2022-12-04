<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

interface EventListener
{
    /**
     * Process the event
     *
     * @param mixed $payload
     * @return mixed
     */
    public function process($payload): void;
}
