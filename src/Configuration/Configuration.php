<?php

namespace Bow\Configuration;

use Bow\Event\Event;
use Bow\Support\Capsule;

abstract class Configuration
{
    /**
     * @var Capsule
     */
    protected $container;

    /**
     * Service constructor.
     *
     * @param Capsule $container
     */
    public function __construct(Capsule $container)
    {
        $this->container = $container;
    }

    /**
     * Permet de créer le service
     *
     * @param Loader $config
     * @return void
     */
    abstract public function create(Loader $config);

    /**
     * Permet de lancer le service
     *
     * @return void
     */
    abstract public function run();

    /**
     * Start listener
     *
     * @param callable $cb
     * @return void
     */
    public function runned($cb)
    {
        Event::once(static::class.'\Service\Started', $cb);
    }

    /**
     * Make listener
     *
     * @param callable $cb
     * @return void
     */
    public function ctreated($cb)
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
