<?php

namespace Bow\Event;

use Bow\Session\Session;
use Bow\Support\Collection;
use Bow\Application\Actionner;

class Event
{
    final private function __clone()
    {
    }

    /**
     * @var array
     */
    private static $events = [];

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
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * addEventListener
     *
     * @param string $event
     * @param Callable|array|string $fn
     * @param int $priority Le namespace de la classe ou fonction à
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
     * Envoyer une event de page en page
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

        if (!is_string($fn)) {
            throw new EventException('Transmission event must be string fonction name');
        }

        static::$events['__bow.transmission.event'][$event][] = new Listener($fn, $priority);

        Session::add("__bow.event.listener", static::$events['__bow.transmission.event']);
    }

    /**
     * Associer un seul listener à un event
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
     * emit dispatchEvent
     *
     * @param  string $event Le nom de l'évènement
     * @return bool
     */
    public static function emit($event)
    {
        if (isset(static::$events['__bow.once.event'][$event])) {
            $listener = static::$events['__bow.once.event'][$event];

            $data = array_slice(func_get_args(), 1);

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

        $data = array_slice(func_get_args(), 1);

        $listeners->each(function (Listener $listener) use ($data) {

            if ($listener->getActionType() === 'string') {
                $callable = $listener->getAction();
            } else {
                $callable = [$listener, 'call'];
            }

            return Actionner::getInstance()->call($callable, [$data]);
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
        if (static::bound($event)) {
            unset(
                static::$events[$event],
                static::$events['__bow.transmission.event'][$event],
                static::$events['__bow.once.event'][$event]
            );
        }
    }

    /**
     * Permet de vérifier si un evenement est déja enregistre au moin un fois.
     *
     * @param  string $event
     * @return bool
     */
    public static function bound($event)
    {
        return array_key_exists($event, static::$events)
            || array_key_exists(
                $event,
                isset(static::$events['__bow.transmission.event'])
                    ? static::$events['__bow.transmission.event'] :
                    []
            )
            || array_key_exists(
                $event,
                isset(static::$events['__bow.once.event']) ? static::$events['__bow.once.event'] : []
            );
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

        throw new \RuntimeException('La methode '.$name.' n\'exists pas.');
    }
}
