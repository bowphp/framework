<?php

namespace Bow\Event;

class Listener
{
    /**
     * The callable
     *
     * @var callable
     */
    private $callable;

    /**
     * The priority index
     *
     * @var int
     */
    private $priority = 0;

    /**
     * Listener constructor.
     *
     * @param callable|string $callable
     * @param int             $priority
     */
    public function __construct($callable, $priority)
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
    public function call(array $data)
    {
        $callable = $this->callable;

        if (is_string($this->callable) && class_exists($this->callable, true)) {
            $instance = app($this->callable);
            if ($instance instanceof EventListerner) {
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
    public function getActionType()
    {
        return gettype($this->callable);
    }

    /**
     * Returns the action to launch
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->callable;
    }

    /**
     * Retrieves the priority of the listener
     *
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
