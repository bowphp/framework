<?php

namespace Bow\Security;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class CryptoConfiguration extends Configuration
{
    /**
     * Configuration du service
     *
     * @param Loader $config
     * @return void
     */
    public function create(Loader $config)
    {
        $this->container->bind('security', function () use ($config) {
            Crypto::setkey($config['security.key'], $config['security.cipher']);

            return Crypto::class;
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function run()
    {
        $this->container->make('security');
    }
}
