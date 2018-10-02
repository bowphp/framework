<?php

namespace Bow\Event;

class Listener
{
    /**
     * @var Callable
     */
    private $callable;

    /**
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
     * Permet de lancer la fonction du listener
     *
     * @param  array $data
     * @return mixed
     */
    public function call(array $data)
    {
        return call_user_func_array($this->callable, $data);
    }

    /**
     * Permet de retourner le type de l'action
     *
     * @return string
     */
    public function getActionType()
    {
        return gettype($this->callable);
    }

    /**
     * Permet de retourner l'action à lancer
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->callable;
    }

    /**
     * Permet de récuperer la priorité du listerner
     *
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
