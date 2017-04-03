<?php
namespace Bow\Event;

use Bow\Session\Session;
use Bow\Application\Actionner;
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

    /**
     * @var array
     */
    private static $events = [];

    /**
     * @var array
     */
    private static $namespace = 'App\\Controller';

    /**
     * @var Event
     */
    private static $instance;

    /**
     * Event constructor.
     *
     * @return Event
     */
    public static function instance()
    {
        if (static::$instance == null) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * addEventListener
     *
     * @param string $event Le nom de l'évènement
     * @param Callable|array|string $fn  La fonction a lancé quand l'évènement se déclanche
     * @param int $priority Le namespace de la classe ou fonction à lancer
     */
    public static function on($event, $fn, $priority = 0)
    {
        if (! static::hasEvent($event)) {
            static::$events[$event] = [];
        }
        static::$events[$event][] = (new Listener($fn, $priority));
        uasort(static::$events[$event], function (Listener $a, Listener $b) {
            return $a->getPriority() < $b->getPriority();
        });
        Session::add("bow.event.listener", static::$events);
    }

    /**
     * emit dispatchEvent
     *
     * @param string $event Le nom de l'évènement
     */
    public static function emit($event)
    {
        if (! static::hasEvent($event)) {
            return;
        }
        $listeners = static::$events[$event];
        $data = array_slice(func_get_args(), 1);
        $listeners->each(function(Listener $listener) use ($data) {
            return Actionner::call([$listener, 'call'], $data, static::$nameSpace);
        });
    }

    /**
     * off supprime un event enregistre
     *
     * @param string $event
     */
    public static function off($event)
    {
        if (static::hasEvent($event)) {
            unset(static::$events[$event]);
        }
    }

    /**
     * Permet de vérifier si un evenement est déja enregistre au moin un fois.
     *
     * @param $event
     * @return bool
     */
    private function hasEvent($event)
    {
        return array_key_exists($event, static::$events);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }
        throw new \RuntimeException('La methode '.$name.' n\'exists pas.');
    }
}