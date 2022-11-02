<?php

declare(strict_types=1);

namespace Bow\Event;

use Bow\Container\Action;
use Bow\Session\Session;
use Bow\Support\Collection;

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
    public static function on($event, $fn, $priority = 0)
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
     * Send an event page to page
     *
     * @param string $event
     * @param callable|array|string $fn
     * @param int $priority
     * @throws EventException
     */
    public static function onTransmission(string $event, callable|array|string $fn, int $priority = 0)
    {
        if (!static::bound($event)) {
            static::$events['__bow.transmission.event'][$event] = [];
        }

        if (!is_string($fn) || !is_array($fn)) {
            throw new EventException('The transmission event must be a string function name');
        }

        static::$events['__bow.transmission.event'][$event][] = new Listener($fn, $priority);

        Session::getInstance()->add("__bow.event.listener", static::$events['__bow.transmission.event']);
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
     * @param  string $event
     * @return bool
     */
    public static function emit(string $event): ?bool
    {
        $data = array_slice(func_get_args(), 1);
        
        if (isset(static::$events['__bow.once.event'][$event])) {
            $listener = static::$events['__bow.once.event'][$event];

            return $listener->call($data);
        }

        if (!static::bound($event)) {
            return false;
        }

        if (isset(static::$events[$event])) {
            $events = static::$events[$event];
        } else {
            $events = static::$events['__bow.transmission.event'][$event];
        }

        $listeners = new Collection($events);

        $listeners->each(function (Listener $listener) use ($data) {
            if ($listener->getActionType() === 'string') {
                $callable = $listener->getAction();
            } else {
                $callable = [$listener, 'call'];
            }

            return Action::getInstance()->execute($callable, [$data]);
        });

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
                static::$events['__bow.transmission.event'][$event],
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
        $translations = static::$events['__bow.transmission.event'] ?? [];

        return array_key_exists($event, $onces)
            || array_key_exists($event, static::$events)
            || array_key_exists($event, $translations);
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
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \RuntimeException('The method '.$name.' There is no');
    }
}
