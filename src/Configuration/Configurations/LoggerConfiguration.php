<?php

namespace Bow\Configuration\Configurations;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerConfiguration extends Configuration
{
    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('logger');
    }
}
