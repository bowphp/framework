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
     * @param callable $callable
     * @param int $priority
     */
    public function __construct(Callable $callable, $priority)
    {
        $this->callable = $callable;
        $this->priority = $priority;
    }

    /**
     * Permet de lance la fonction du listener
     *
     * @param array $data
     * @return mixed
     */
    public function call(array $data)
    {
        return call_user_func_array($this->callable, $data);
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