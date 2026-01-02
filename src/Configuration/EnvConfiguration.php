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
        
        // Only configure if file exists and environment is not already loaded
        try {
            $env = Env::getInstance();
            if ($env->isLoaded()) {
                $this->container->instance('env', $env);
                return;
            }
        } catch (\Bow\Application\Exception\ApplicationException $e) {
            // Environment not loaded, continue to load it
        }
        
        if (file_exists($envFile)) {
            Env::configure($envFile);
            $event = Env::getInstance();
            $this->container->instance('env', $event);
        }
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        // Nothing to do
    }
}
