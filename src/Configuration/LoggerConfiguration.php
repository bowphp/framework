<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\View\View;
use Exception;
use Monolog\Logger;
use Bow\Support\Collection;
use Whoops\Handler\Handler;
use Bow\Database\Barry\Model;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Whoops\Handler\CallbackHandler;
use Bow\Contracts\ResponseInterface;
use Iterator;
use Whoops\Handler\PrettyPageHandler;

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
     * @param $error_handler
     * @return void
     */
    private function loadFrontLogger(Logger $monolog, $error_handler): void
    {
        $whoops = new \Whoops\Run();

        if (app_env('APP_DEBUG')) {
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
                    $result instanceof Model || $result instanceof Collection
                    || is_array($result)
                    || is_object($result)
                    || $result instanceof Iterator
                ) {
                    echo json_encode($result);
                } elseif (is_string($result)) {
                    echo $result;
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
     * @throws Exception
     */
    private function loadFileLogger(string $log_dir, string $name): Logger
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
