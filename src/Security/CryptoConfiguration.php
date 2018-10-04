<?php

namespace Bow\Security;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class CryptoConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('security', function () use ($config) {
            Crypto::setkey($config['security.key'], $config['security.cipher']);

            return Crypto::class;
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('security');
    }
}
