<?php

namespace Bow\Configuration;

use Bow\Contracts\ResponseInterface;
use Bow\Database\Barry\Model;
use Bow\Support\Collection;
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

            if (php_sapi_name() != "cli") {
                $this->loadFrontLogger($monolog, $config['app.error_handle']);
            }

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

                    $result = call_user_func_array(
                        [new $error_handler, 'handle'],
                        [$exception]
                    );

                    switch (true) {
                        case is_null($result):
                        case is_string($result):
                        case is_array($result):
                        case is_object($result):
                        case $result instanceof \Iterable:
                            return $result;
                        case $result instanceof ResponseInterface:
                            return $result->sendContent();
                        case $result instanceof Model || $result instanceof Collection:
                            return $result->toArray();
                    }
                    exit(1);
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
            new StreamHandler($log_dir . '/bow-'.date('Y-m-d').'.log', Logger::DEBUG)
        );

        $monolog->pushHandler(
            new FirePHPHandler()
        );

        return $monolog;
    }
}
