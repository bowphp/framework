<?php
namespace Bow\Support\Session;

use App\Actionner;
use Bow\Support\Util;
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
     * @param string $event Le nom de l'évènement
     * @param Callable|array|string $fn  La fonction a lancé quand l'évènement se déclanche
     * @param array $nameSpace Le namespace de la classe ou fonction à lancer
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

        if (!$ref->has($eventName)) {
            $ref->add($eventName, $bindFunction);
        } else {
            $ref->add($eventName, $bindFunction);
        }
    }

    /**
     * emit dispatchEvent
     *
     * @param string $event Le nom de l'évènement
     * @param array $data Donnée supplementaire
     * @throws EventException
     */
    public static function emit($event, $data = [])
    {
        static::$events = Session::get("bow.event.function");
        $isEmpty = true;

        if (static::$events instanceof Collection) {
            static::$events->collectionify($event)->each(function($fn) use ($data) {
                return Actionner::call($fn, $data, static::$nameSpace);
            });
            $isEmpty = false;
        }

        if (static::$eventCallback instanceof Collection) {
            static::$eventCallback->collectionify($event)->each(function($fn) use ($data) {
                return Actionner::call($fn, $args);
            });
            $isEmpty = false;
        }

        if ($isEmpty) {
            throw new EventException("Aucun évènement n'est pas enregistré.", E_USER_ERROR);
        }
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
            Actionner::call($cb);
        }
    }
}