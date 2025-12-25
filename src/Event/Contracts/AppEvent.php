<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

interface AppEvent
{
    /**
     * Get the name of the event
     *
     * @return string
     */
    public function getName(): string;
}
