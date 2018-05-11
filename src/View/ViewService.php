<?php

namespace Bow\View;

use Bow\View\View;
use Bow\Config\Config;
use Bow\Application\Service as BowService;

class ViewService extends BowService
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
        $this->app->capsule(View::class, function () use ($config) {
            View::configure($config);
            return View::getInstance();
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(View::class);
    }
}
