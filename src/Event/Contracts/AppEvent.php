<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

interface AppEvent
{
    /**
     * Dispatch the event
     *
     * @return mixed
     */
    public static function dispatch(): mixed;
}
