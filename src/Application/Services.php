<?php

namespace Bow\Application;

use Bow\Event\Event;

abstract class Services
{
    protected $app;

    /**
     * Services constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param Application $app
     */
    abstract public function make($app);

    /**
     * @return mixed
     */
    abstract public function start();

    /**
     * @param callable $cb
     */
    public function stared($cb)
    {
        Event::once(static::class.'.service.started', $cb);
    }

    /**
     * @param callable $cb
     */
    public function maked($cb)
    {
        Event::once(static::class.'.service.maked', $cb);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return get_called_class();
    }
}