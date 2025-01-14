<?php

declare(strict_types=1);

namespace Bow\Event;

use Bow\Event\Contracts\AppEvent;
use ErrorException;

class Event
{
    /**
     * The event collector
     *
     * @var array
     */
    private static $events = [];

    /**
     * The Event instance
     *
     * @var Event
     */
    private static $instance;

    /**
     * Event constructor.
     *
     * @return Event
     */
    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new Event();
        }

        return static::$instance;
    }

    /**
     * addEventListener
     *
     * @param string $event
     * @param callable|array|string $fn
     * @param int $priority
     */
    public static function on(string $event, callable|string $fn, int $priority = 0)
    {
        if (!static::bound($event)) {
            static::$events[$event] = [];
        }

        static::$events[$event][] = new Listener($fn, $priority);

        uasort(static::$events[$event], function (Listener $first_listener, Listener $second_listener) {
            return $first_listener->getPriority() < $second_listener->getPriority();
        });
    }

    /**
     * Associate a single listener to an event
     *
     * @param string $event
     * @param callable|array|string $fn
     * @param int $priority
     */
    public static function once(string $event, callable|array|string $fn, int $priority = 0): void
    {
        static::$events['__bow.once.event'][$event] = new Listener($fn, $priority);
    }

    /**
     * Dispatch event
     *
     * @param  string|AppEvent $event
     * @return bool
     */
    public static function emit(string|AppEvent $event): ?bool
    {
        $event_name = $event;

        if ($event instanceof AppEvent) {
            $event_name = get_class($event);
            $data = [$event];
        } else {
            $data = array_slice(func_get_args(), 1);
        }

        if (!static::bound($event_name)) {
            throw new EventException("The $event_name not found");
        }

        if (isset(static::$events['__bow.once.event'][$event_name])) {
            $listener = static::$events['__bow.once.event'][$event_name];

            return $listener->call($data);
        }

        $events = (array) static::$events[$event_name];

        // Execute each listener
        collect($events)->each(fn (Listener $listener) => $listener->call($data));

        return true;
    }

    /**
     * off removes an event saves
     *
     * @param string $event
     */
    public static function off(string $event): void
    {
        if (static::bound($event)) {
            unset(
                static::$events[$event],
                static::$events['__bow.once.event'][$event]
            );
        }
    }

    /**
     * Check whether an event is already recorded at least once.
     *
     * @param  string $event
     * @return bool
     */
    public static function bound(string $event): bool
    {
        $onces = static::$events['__bow.once.event'] ?? [];

        return array_key_exists($event, $onces) || array_key_exists($event, static::$events);
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (is_null(static::$instance)) {
            throw new ErrorException(
                "Unable to get event instance before configuration"
            );
        }

        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \RuntimeException('The method ' . $name . ' There is no');
    }
}
