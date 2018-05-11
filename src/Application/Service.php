<?php

namespace Bow\Application;

use Bow\Event\Event;
use Bow\Config\Config;

abstract class Service
{
    protected $app;

    /**
     * Service constructor.
     *
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Permet de créer le service
     *
     * @param Config $config
     * @return void
     */
    abstract public function make(Config $config);

    /**
     * Permet de lancer le service
     *
     * @return void
     */
    abstract public function start();

    /**
     * Start listener
     *
     * @param callable $cb
     * @return void
     */
    public function stared($cb)
    {
        Event::once(static::class.'\Service\Started', $cb);
    }

    /**
     * Make listener
     *
     * @param callable $cb
     * @return void
     */
    public function maked($cb)
    {
        Event::once(static::class.'\Service\Maked', $cb);
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
