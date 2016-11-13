<?php
namespace Bow\Support\Session;

use Bow\Application\Loader;
use Bow\Support\Collection;
use Bow\Exception\EventException;

/**
 * Class Event
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Event
{
    final private function __clone(){}
    final private function __construct(){}

    /**
     * @var Collection
     */
    private static $events;

    /**
     * @var Collection
     */
    private static $eventCallback;

    /**
     * @var array
     */
    private static $nameSpace = [];

    /**
     * addEventListener
     *
     * @param string                $event     Le nom de l'évènement
     * @param Callable|array|string $fn        La fonction a lancé quand l'évènement se déclanche
     * @param array                 $nameSpace Le namespace de la classe ou fonction à lancer
     */
    public static function on($event, $fn, array $nameSpace = [])
    {
        if (! is_callable($fn)) {
            static::$nameSpace = $nameSpace;
            static::addEvent($event, $fn, "events");
            Session::add("bow.event.function", static::$events);
        } else {
            static::addEvent($event, $fn, "eventCallback");
        }
    }

    /**
     * Ajout un nouvelle evenement
     *
     * @param string          $eventName
     * @param Callable|string $bindFunction
     * @param string          $eventType
     */
    private static function addEvent($eventName, $bindFunction, $eventType)
    {
        $ref = & static::${$eventType};

        if ($ref === null) {
            $ref = new Collection();
        }

        $ref->add($eventName, $bindFunction);
    }

    /**
     * emit dispatchEvent
     *
     * @param string $event Le nom de l'évènement
     * @throws EventException
     *
     * @return boolean
     */
    public static function emit($event)
    {
        $args = array_slice(func_get_args(), 1);
        static::$events = new Collection(Session::get("bow.event.function"));

        if (static::$events instanceof Collection) {
            static::$events->each(function($fn, $register_event) use ($args, $event)
            {
                if ($register_event === $event) {
                   Loader::launch($fn, $args, static::$nameSpace);
                }
            });
            return true;
        }

        if (static::$eventCallback instanceof Collection) {
            static::$eventCallback->each(function($fn, $register_event) use ($args, $event)
            {
                if ($register_event === $event) {
                    Loader::launch($fn, $args);
                }
            });
            return true;
        }

        throw new EventException("Aucun évènement n'est pas enregistré.", E_USER_ERROR);
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
            static::$events->delete($event);

            Loader::launch($cb);
        }
    }
}