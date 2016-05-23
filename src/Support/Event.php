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
     * @param string   $event Le nom de l'évènement
     * @param Callable $fn    La fonction a lancé quand l'évènement se déclanche
     */
    public static function on($event, Callable $fn)
    {
        if (static::$events === null) {
            static::$events = new Collection();
        }

        if (!static::$events->has($event)) {
            static::$events->add($event, $fn);
        } else {
            static::$events->update($event, $fn);
        }
    }

    /**
     * emit dispatchEvent
     *
     * @param string $event Le nom de l'évènement
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
        if (static::$events->has($event)) {
            static::$events->remove($event);

            Util::launchCallback($cb);
        }
    }
}