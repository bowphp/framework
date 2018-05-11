<?php

namespace Bow\Mail;

use Bow\Config\Config;
use Bow\Application\Service as BowService;

class MailService extends BowService
{
    /**
     * Configuration du service
     *
     * @param Config $config
     * @return void
     */
    public function make(Config $config)
    {
        $this->app->capsule(Mail::class, function () use ($config) {
            return Mail::configure($config['mail']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(Mail::class);
    }
}
