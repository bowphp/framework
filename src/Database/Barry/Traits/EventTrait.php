<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

use Bow\Support\Str;

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
        return str_replace('\\', '', strtolower(Str::snake(static::class))) . '.' . Str::snake($event);
    }

    /**
     * Fire event
     *
     * @param string $event
     */
    private function fireEvent(string $event): void
    {
        $env = $this->formatEventName($event);

        if (event()->bound($env)) {
            event()->emit($env, $this);
        }
    }
}
