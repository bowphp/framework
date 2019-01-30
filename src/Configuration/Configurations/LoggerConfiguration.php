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
            if (app_env('APP_ENV') == 'development') {
                $this->loadFrontLogger();
            }

            return $this->loadFileLogger(
                $config['storage.log'],
                $config['app.name'] ?? 'Bow'
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('logger');
    }

    /**
     * Loader view logger
     *
     * @return void
     */
    private function loadFrontLogger()
    {
        $whoops = new \Whoops\Run;

        $whoops->pushHandler(
            new \Whoops\Handler\PrettyPageHandler
        );

        $whoops->register();
    }

    /**
     * Loader file logger via Monolog
     *
     * @param string $log_dir
     * @param string $name
     * @return Logger
     * @throws \Exception
     */
    private function loadFileLogger($log_dir, $name)
    {
        $monolog = new Logger($name);

        $monolog->pushHandler(
            new StreamHandler($log_dir . '/bow.log', Logger::DEBUG)
        );

        $monolog->pushHandler(
            new FirePHPHandler()
        );

        return $monolog;
    }
}
