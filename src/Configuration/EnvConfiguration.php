<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\Support\Env;

class EnvConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        Env::configure($config->getPath('.env.json') ?? null);

        $event = Env::getInstance();

        $this->container->instance('env', $event);
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        //
    }
}
