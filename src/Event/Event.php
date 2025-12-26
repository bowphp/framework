<?php

declare(strict_types=1);

namespace Bow\Event;

use Bow\Event\Contracts\AppEvent;
use ErrorException;
use RuntimeException;

/**
 * Class Event
 *
 * @package Bow\Event
 * @method static void on(string $event, callable|string $fn, int $priority = 0)
 * @method static void once(string $event, callable|array|string $fn, int $priority = 0)
 * @method static ?bool emit(string|AppEvent $event)
 * @method static void off(string $event)
 * @method static ?bool dispatch(string|AppEvent $event)
 */
class Event
{
    /**
     * The event collector
     *
     * @var array
     */
    private static array $events = [];

    /**
     * The Event instance
     *
     * @var ?Event
     */
    private static ?Event $instance = null;

    /**
     * Event constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (static::$instance != null) {
            throw new \Exception("The Event class is a singleton and already instantiated. Please use Event::getInstance() to get the instance.");
        }
    }

    /**
     * Event constructor.
     *
     * @return Event
     */
    public static function getInstance(): Event
    {
        if (static::$instance == null) {
            static::$instance = new Event();
        }

        return static::$instance;
    }

    /**
     * addEventListener
     *
     * @param string          $event
     * @param callable|string $fn
     * @param int             $priority
     */
    public function on(string $event, callable|string $fn, int $priority = 0): void
    {
        if (!static::bound($event)) {
            static::$events[$event] = [];
        }

        static::$events[$event][] = new Listener($fn, $priority);

        uasort(
            static::$events[$event],
            function (Listener $first_listener, Listener $second_listener) {
                return $second_listener->getPriority() <=> $first_listener->getPriority();
            }
        );
    }

    /**
     * Alias to on method
     *
     * @param string          $event
     * @param callable|string $fn
     * @param int             $priority
     */
    public function listener(string $event, callable|string $fn, int $priority = 0): void
    {
        $this->on($event, $fn, $priority);
    }

    /**
     * Check whether an event is already recorded at least once.
     *
     * @param  string|AppEvent $event
     * @return bool
     */
    public function bound(string|AppEvent $event): bool
    {
        $onces = static::$events['__bow.once.event'] ?? [];

        return array_key_exists($event, static::$events) ||
            array_key_exists($event, $onces);
    }

    /**
     * Associate a single listener to an event
     *
     * @param string                $event
     * @param callable|array|string $fn
     * @param int                   $priority
     */
    public function once(string $event, callable|array|string $fn, int $priority = 0): void
    {
        static::$events['__bow.once.event'][$event] = new Listener($fn, $priority);
    }

    /**
     * Get the one-time listener for an event
     *
     * @param  string $event
     * @return Array<Listener>
     */
    public function getEventListeners(string $event_name): array
    {
        $once_event = static::$events['__bow.once.event'][$event_name] ?? null;

        if ($once_event) {
            return [$once_event];
        }

        $regular_events = static::$events[$event_name] ?? [];

        return (array) $regular_events;
    }

    /**
     * Dispatch event
     *
     * @param  string|AppEvent $event
     * @return bool|null
     * @throws EventException
     */
    public function emit(string|AppEvent $event): ?bool
    {
        $event_name = $event;

        if ($event instanceof AppEvent) {
            $event_name = get_class($event);
            $data = [$event];
        } else {
            $data = array_slice(func_get_args(), 1);
        }

        if (!$this->bound($event_name)) {
            return null;
        }

        $events = $this->getEventListeners($event_name);

        // Execute each listener
        collect($events)->each(fn(Listener $listener) => $listener->call($data));

        return true;
    }

    /**
     * Dispatch event
     *
     * @param  string|AppEvent $event
     * @return bool|null
     * @throws EventException
     */
    public function dispatch(string|AppEvent $event): ?bool
    {
        return $this->emit($event);
    }

    /**
     * off removes an event saves
     *
     * @param string|AppEvent $event
     */
    public function off(string|AppEvent $event): void
    {
        if ($this->bound($event)) {
            unset(static::$events[$event], static::$events['__bow.once.event'][$event]);
        }
    }

    /**
     * __callStatic
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     * @throws ErrorException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (is_null(static::$instance)) {
            throw new ErrorException(
                "Unable to get event instance before configuration"
            );
        }

        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new RuntimeException('The method ' . $name . ' There is no');
    }
}
