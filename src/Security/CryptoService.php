<?php

namespace Bow\Security;

use Bow\Config\Config;
use Bow\Security\Crypto;
use Bow\Application\Service as BowService;

class CryptoService extends BowService
{
    /**
     * Configuration du service
     *
     * @param Config $config
     * @return void
     */
    public function make(Config $config)
    {
        $this->app->capsule(Crypto::class, function () use ($config) {
            Crypto::setkey(
                $config['security.key'],
                $config['security.cipher']
            );

            return Crypto::class;
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(Crypto::class);
    }
}
