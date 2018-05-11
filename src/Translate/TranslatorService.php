<?php

namespace Bow\Translate;

use Bow\Config\Config;
use Bow\Application\Service as BowService;

class TranslatorService extends BowService
{
    /**
     * __
     *
     * @param Config $config
     * @return void
     */
    public function make(Config $config)
    {
        /**
         * Configuration de translator
         */
        $this->app->capsule(Translator::class, function () use ($config) {
            return Translator::configure(
                $config['trans.lang'],
                $config['trans.directory']
            );
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(Translator::class);
    }
}
