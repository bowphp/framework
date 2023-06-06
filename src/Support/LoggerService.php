<?php

namespace Bow\Support;

class LoggerService
{
    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function error(string $message, array $context = [])
    {
        app('logger')->error($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function info(string $message, array $context = [])
    {
        app('logger')->info($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function warning(string $message, array $context = [])
    {
        app('logger')->warning($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function alert(string $message, array $context = [])
    {
        app('logger')->alert($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function critical(string $message, array $context = [])
    {
        app('logger')->critical($message, $context);
    }

    /**
     * Logger service
     *
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public function emergency(string $message, array $context = [])
    {
        app('logger')->emergency($message, $context);
    }
}
