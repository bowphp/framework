<?php

namespace Bow\Mail;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class MailConfiguration extends Configuration
{
    /**
     * Configuration du service
     *
     * @param Loader $config
     * @return void
     */
    public function create(Loader $config)
    {
        $this->container->bind('mail', function () use ($config) {
            return Mail::configure($config['mail']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function run()
    {
        $this->container->make('mail');
    }
}
