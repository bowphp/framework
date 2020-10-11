<?php

namespace Bow\Auth;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class AuthenticationConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('auth', function () use ($config) {
            return Auth::configure($config['auth']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('auth');
    }
}
