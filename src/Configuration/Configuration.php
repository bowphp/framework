<?php

namespace Bow\Configuration;

use Bow\Support\Capsule as Container;

abstract class Configuration
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Service constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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

    /**
     * Create the service
     *
     * @param Loader $config
     * @return void
     */
    abstract public function create(Loader $config);

    /**
     * Start the configured package
     *
     * @return void
     */
    abstract public function run();
}
