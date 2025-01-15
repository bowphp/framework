<?php

namespace Bow\Support;

class LoggerService
{
    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        app('logger')->error($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        app('logger')->info($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        app('logger')->warning($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        app('logger')->alert($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        app('logger')->critical($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        app('logger')->emergency($message, $context);
    }
}
