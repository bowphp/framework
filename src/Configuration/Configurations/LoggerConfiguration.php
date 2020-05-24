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
            $monolog = $this->loadFileLogger(
                realpath($config['storage.log']),
                $config['app.name'] ?? 'Bow'
            );

            $this->loadFrontLogger($monolog, $config['app.error_handle']);

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

    /**
     * Loader view logger
     *
     * @param Logger $monolog
     * @return void
     */
    private function loadFrontLogger(Logger $monolog, $error_handler)
    {
        $whoops = new \Whoops\Run;

        if (app_env('APP_ENV') == 'development') {
            $whoops->pushHandler(
                new \Whoops\Handler\PrettyPageHandler
            );
        }

        if (class_exists($error_handler)) {
            $handler = new \Whoops\Handler\CallbackHandler(
                function ($exception, $inspector, $run) use ($monolog, $error_handler) {
                    $monolog->error($exception->getMessage(), $exception->getTrace());

                    return call_user_func_array(
                        [new $error_handler, 'handle'],
                        [$exception]
                    );
                }
            );

            $whoops->pushHandler($handler);
        }

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
