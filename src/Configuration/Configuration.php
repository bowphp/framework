<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\Container\Capsule as Container;

abstract class Configuration
{
    /**
     * @var Container
     */
    protected Container $container;

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
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get la service class name
     *
     * @return string
     */
    public function getName(): string
    {
        return get_called_class();
    }

    /**
     * Create and configure the server or package
     *
     * @param Loader $config
     * @return void
     */
    abstract public function create(Loader $config): void;

    /**
     * Start the configured package
     *
     * @return void
     */
    abstract public function run(): void;
}
