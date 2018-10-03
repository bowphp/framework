<?php

namespace Bow\Configuration\Configurations;

use Monolog\Logger;
use Bow\Configuration\Loader;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Bow\Configuration\Configuration;

class LoggerConfiguration extends Configuration
{
    /**
     * Permet de créer le service
     *
     * @param Loader $config
     * @return void
     */
    public function create(Loader $config)
    {
        $this->container->bind('logger', function () use ($config) {
            $whoops = new \Whoops\Run;

            $monolog = new Logger('BOW');

            $whoops->pushHandler(
                new \Whoops\Handler\PrettyPageHandler
            );

            $whoops->register();

            $monolog->pushHandler(
                new StreamHandler($config['resource.log'] . '/bow.log', Logger::DEBUG)
            );

            $monolog->pushHandler(
                new FirePHPHandler()
            );

            return $monolog;
        });
    }

    /**
     * Permet de lancer le service
     *
     * @return void
     */
    public function run()
    {
        $this->container->make('logger');
    }
}
