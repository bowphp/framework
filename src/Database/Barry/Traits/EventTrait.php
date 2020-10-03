<?php

namespace Bow\Database\Barry\Traits;

class EventTrait
{
    /**
     * Get event name
     *
     * @param string $event
     * @return mixed
     */
    private static function formatEventName($event)
    {
        return str_replace('\\', '.', strtolower(static::class)).'.'.$event;
    }

    /**
     * Fire event
     *
     * @param string $event
     */
    private function fireEvent($event)
    {
        $env = $this->formatEventName($event);

        if (emitter()->bound($env)) {
            emitter()->emit($env, $this);
        }
    }
}
