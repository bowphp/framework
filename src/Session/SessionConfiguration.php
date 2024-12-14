<?php

declare(strict_types=1);

namespace Bow\Session;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Security\Tokenize;

class SessionConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('session', function () use ($config) {
            $session = Session::configure((array) $config['session']);

            Tokenize::makeCsrfToken((int) $config['session.lifetime']);

            // Reboot the old request values
            Session::getInstance()->add('__bow.old', []);

            return $session;
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('session');
    }
}
