<?php

declare(strict_types=1);

namespace Bow\Container;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class ContainerConfiguration extends Configuration
{
    /**
     * @var array
     */
    private array $middlewares = [
        //
    ];

    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('action', function () use ($config) {
            $middlewares = array_merge($config->getMiddlewares(), $this->middlewares);

            return Action::configure($config->namespaces(), $middlewares);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('action');
    }
}
