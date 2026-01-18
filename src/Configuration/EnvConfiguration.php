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
        $this->container->bind('env', function () {
            Env::configure(base_path('.env.json'));

            $event = Env::getInstance();

            $this->container->instance('env', $event);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('env');
    }
}
