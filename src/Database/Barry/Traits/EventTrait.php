<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

use Bow\Support\Str;

trait EventTrait
{
    /**
     * Fire event
     *
     * @param string $event
     */
    private function fireEvent(string $event): void
    {
        $env = static::formatEventName($event);

        if (event()->bound($env)) {
            event()->emit($env, $this);
        }
    }

    /**
     * Get event name
     *
     * @param string $event
     * @return string
     */
    private static function formatEventName(string $event): string
    {
        $class_name = str_replace('\\', '', strtolower(Str::snake(static::class)));

        return sprintf("%s.%s", $class_name, strtolower($event));
    }
}
