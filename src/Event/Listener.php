<?php

declare(strict_types=1);

namespace Bow\Event;

use Bow\Event\Contracts\EventListener;
use Bow\Event\Contracts\EventShouldQueue;

class Listener
{
    /**
     * The callable
     *
     * @var callable
     */
    private mixed $callable;

    /**
     * The priority index
     *
     * @var int
     */
    private int $priority = 0;

    /**
     * Listener constructor.
     *
     * @param callable|string $callable
     * @param int $priority
     */
    public function __construct(callable|string $callable, int $priority)
    {
        $this->callable = $callable;

        $this->priority = $priority;
    }

    /**
     * Launch the listener function
     *
     * @param  array $data
     * @return mixed
     */
    public function call(array $data = []): mixed
    {
        $callable = $this->callable;

        if (is_string($callable) && class_exists($callable, true)) {
            $instance = app($callable);
            if ($instance instanceof EventListener) {
                if ($instance instanceof EventShouldQueue) {
                    queue(new EventProducer($instance, $data));
                    return null;
                }
                $callable = [$instance, 'process'];
            }
        }

        return call_user_func_array($callable, $data);
    }

    /**
     * Returns the type of action
     *
     * @return string
     */
    public function getActionType(): string
    {
        return gettype($this->callable);
    }

    /**
     * Returns the action to launch
     *
     * @return mixed
     */
    public function getAction(): mixed
    {
        return $this->callable;
    }

    /**
     * Retrieves the priority of the listener
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
