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
        $envFile = $config->getBasePath() . '/.env.json';

        // Check if environment is already loaded
        try {
            $env = Env::getInstance();
            if ($env->isLoaded()) {
                $this->container->instance('env', $env);
                return;
            }
        } catch (\Bow\Application\Exception\ApplicationException $e) {
            // Environment not loaded, continue to load it
        }

        // Load environment - will throw exception if file doesn't exist
        Env::configure($envFile);

        $event = Env::getInstance();

        $this->container->instance('env', $event);
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        // Nothing to do
    }
}
