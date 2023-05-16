<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\View\View;
use Monolog\Logger;
use Bow\Support\Collection;
use Bow\Configuration\Loader;
use Bow\Database\Barry\Model;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Whoops\Handler\CallbackHandler;
use Bow\Configuration\Configuration;
use Bow\Contracts\ResponseInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\Handler;

class LoggerConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
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
    public function run(): void
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
        $whoops = new \Whoops\Run();

        if (app_env('APP_ENV') != 'production') {
            $whoops->pushHandler(new PrettyPageHandler());
            $whoops->register();
            return;
        }

        $handler = new CallbackHandler(
            function ($exception, $inspector, $run) use ($monolog, $error_handler) {
                $monolog->error($exception->getMessage(), $exception->getTrace());

                $result = call_user_func_array([new $error_handler(), 'handle'], [$exception]);

                if ($result instanceof View) {
                    echo $result->getContent();
                } elseif ($result instanceof ResponseInterface) {
                    $result->sendContent();
                } elseif (
                    is_null($result)
                    || $result instanceof Model || $result instanceof Collection
                    || is_string($result)
                    || is_array($result)
                    || is_object($result)
                    || $result instanceof \Iterable
                ) {
                    echo json_encode($result);
                }

                return Handler::QUIT;
            }
        );

        $whoops->pushHandler($handler);
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
            new StreamHandler($log_dir . '/bow-' . date('Y-m-d') . '.log', Logger::DEBUG)
        );

        $monolog->pushHandler(
            new FirePHPHandler()
        );

        return $monolog;
    }
}
