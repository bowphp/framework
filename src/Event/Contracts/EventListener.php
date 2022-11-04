<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

interface EventListener
{
    /**
     * Process the event
     *
     * @param array $payload
     * @return mixed
     */
    public function process(array $payload): void;
}
