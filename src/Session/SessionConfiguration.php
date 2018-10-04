<?php

namespace Bow\Session;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Security\Tokenize;

class SessionConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('session', function () use ($config) {

            $session = Session::configure($config['session']);

            Tokenize::makeCsrfToken($config['session.lifetime']);

            return $session;
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('session');
    }
}
