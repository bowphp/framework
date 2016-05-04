<?php

/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Exception\EventException;

class Event
{
    final private function __clone(){}
    final private function __construct(){}

    /**
     * @var Collection
     */
    private static $events;

    /**
     * addEventListener
     *
     * @param $event
     * @param $fn
     */
    public static function on($event, $fn)
    {
        if (static::$events === null) {
            static::$events = new Collection();
        }

        if (!static::$events->has($event)) {
            static::$events->add($event, $fn);
        } else {
            static::$events->update($event, $fn);
        }

        // Session::add("bow.event", static::$events);
    }

    /**
     * emit dispatchEvent
     *
     * @param $event
     * @throws EventException
     */
    public static function emit($event)
    {
        $args = array_slice(func_get_args(), 1);

        if (! (static::$events instanceof Collection)) {
            throw new EventException("Aucun évènement n'est pas enregistré");
        }

        if (!static::$events->has($event)) {
            throw new EventException("Cette évènement n'est pas enregistré");
        }

        static::$events->collectionify($event)->each(function($fn) use ($args) {
            return call_user_func_array($fn, $args);
        });
    }

    /**
     * off supprime un event enregistre
     *
     * @param string $event
     * @param Callable $cb
     */
    public static function off($event, $cb = null)
    {
        // static::unserialise();

        if (static::$events->has($event)) {
            static::$events->remove($event);

            Util::launchCallback($cb);
        }
    }
}