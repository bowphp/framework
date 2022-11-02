<?php

declare(strict_types=1);

namespace Bow\Event;

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
     * @return bool
     */
    public function call(array $data = []): bool
    {
        $callable = $this->callable;

        if (is_string($this->callable) && class_exists($this->callable, true)) {
            $instance = app($this->callable);
            if ($instance instanceof EventListener) {
                $callable = [$instance, 'process'];
            }
        }

        return (bool) call_user_func_array($callable, $data);
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
