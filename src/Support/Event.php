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
     * @param $event
     * @throws EventException
     */
    public static function emit($event)
    {
        $args = array_slice(func_get_args(), 1);
        // static::unserialise();

        if (static::$events instanceof Collection) {
            if (static::$events->has($event)) {
                static::$events->collectionify($event)->each(function($fn) use ($args) {
                    return call_user_func_array($fn, $args);
                });
            }
        } else {
            throw new EventException("Cette evenement n'est pas enregistré");
        }
    }

    /**
     * off supprime un event enregistre
     * @param $event
     */
    public static function off($event)
    {
        // static::unserialise();

        if (static::$events->has($event)) {
            static::$events->remove($event);
        }

        // Session::add("bow.event", static::$events);
    }

    /**
     * Réconstruit les informations sauvegarder dans la
     * session
     */
    private static function unserialise()
    {
        if (Session::has("bow.event")) {
            if (Session::get("bow.event") instanceof Collection) {
                static::$events = Session::get("bow.event");
            }
        }
    }
}