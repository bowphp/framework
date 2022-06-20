<?php

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

        uasort(static::$events[$event], function (Listener $a, Listener $b) {
            return $a->getPriority() < $b->getPriority();
        });
    }

    /**
     * Send an event page to page
     *
     * @param string $event
     * @param array|string $fn
     * @param int $priority
     * @throws EventException
     */
    public static function onTransmission($event, $fn, $priority = 0)
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
    public static function once($event, $fn, $priority = 0)
    {
        static::$events['__bow.once.event'][$event] = new Listener($fn, $priority);
    }

    /**
     * Emit dispatchEvent
     *
     * @param  string $event Le nom de l'évènement
     * @return bool
     */
    public static function emit($event)
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
    public static function off($event)
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
    public static function bound($event)
    {
        $onces = isset(static::$events['__bow.once.event']) ? static::$events['__bow.once.event'] : [];
        $translations = isset(static::$events['__bow.transmission.event']) ? static::$events['__bow.transmission.event'] : []; // phpcs:ignore

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
    public function __call($name, $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \RuntimeException('The method '.$name.' There is no');
    }
}
