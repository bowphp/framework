<?php
namespace Bow\Event;

use Bow\Session\Session;
use Bow\Support\Collection;
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
    private static $namespace = 'App\\Controllers';

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
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
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
        if (! self::binded($event)) {
            self::$events[$event] = [];
        }

        self::$events[$event][] = new Listener($fn, $priority);

        uasort(self::$events[$event], function (Listener $a, Listener $b) {
            return $a->getPriority() < $b->getPriority();
        });
    }

    /**
     * @param string $event
     * @param callable|array|string $fn
     * @param int $priority
     */
    public static function once($event, $fn, $priority = 0)
    {

    }

    /**
     * @param string $event
     * @param array|string $fn
     * @param int $priority
     */
    public static function onTransmission($event, $fn, $priority = 0)
    {
        if (! self::binded($event)) {
            self::$events[$event] = [];
        }

        self::$events[$event][] = new Listener($fn, $priority);
        Session::add("__bow.event.listener", self::$events);
    }

    /**
     * emit dispatchEvent
     *
     * @param string $event Le nom de l'évènement
     * @return bool
     */
    public static function emit($event)
    {
        if (! self::binded($event)) {
            return false;
        }

        $listeners = new Collection(self::$events[$event]);
        $data = array_slice(func_get_args(), 1);

        $listeners->each(function(Listener $listener) use ($data) {

            if ($listener->getActionType() === 'string') {
                $callable = $listener->getAction();
            } else {
                $callable = [$listener, 'call'];
            }

            return Actionner::call($callable, [$data], [
                'namespace' => [ 'controller' => self::$namespace ]
            ]);
        });

        return true;
    }

    /**
     * off supprime un event enregistre
     *
     * @param string $event
     */
    public static function off($event)
    {
        if (self::binded($event)) {
            unset(self::$events[$event]);
        }
    }

    /**
     * Permet de vérifier si un evenement est déja enregistre au moin un fois.
     *
     * @param string $event
     * @return bool
     */
    public static function binded($event)
    {
        return array_key_exists($event, self::$events);
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
        if (method_exists(self::$instance, $name)) {
            return call_user_func_array([self::$instance, $name], $arguments);
        }

        throw new \RuntimeException('La methode '.$name.' n\'exists pas.');
    }
}