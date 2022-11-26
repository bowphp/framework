<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

trait EventTrait
{
    /**
     * Get event name
     *
     * @param string $event
     * @return string
     */
    private static function formatEventName(string $event): string
    {
        return str_replace('\\', '.', strtolower(static::class)) . '.' . $event;
    }

    /**
     * Fire event
     *
     * @param string $event
     */
    private function fireEvent(string $event): void
    {
        $env = $this->formatEventName($event);

        if (emitter()->bound($env)) {
            emitter()->emit($env, $this);
        }
    }
}
