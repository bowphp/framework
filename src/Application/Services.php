<?php

namespace Bow\Application;

use Bow\Event\Event;
use Bow\Config\Config;

abstract class Services
{
    protected $app;

    /**
     * Services constructor.
     *
     * @param $app
     */
    public function __construct($app = null)
    {
        $this->app = $app;
    }

    /**
     * Permet de créer le service
     *
     * @param Config $config
     * @param Config $config
     */
    abstract public function make(Config $config);

    /**
     * Permet de lancer le service
     *
     * @return mixed
     */
    abstract public function start();

    /**
     * Start listener
     *
     * @param callable $cb
     */
    public function stared($cb)
    {
        Event::once(static::class.'.service.started', $cb);
    }

    /**
     * Make listener
     *
     * @param callable $cb
     */
    public function maked($cb)
    {
        Event::once(static::class.'.service.maked', $cb);
    }

    /**
     * Get la service class name
     *
     * @return string
     */
    public function getName()
    {
        return get_called_class();
    }
}
