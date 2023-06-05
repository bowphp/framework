<?php

namespace Bow\Support;

/**
 * @method static void error(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void emergency(string $message, array $context = [])
 */
class Log
{
    /**
     * Log
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        call_user_func_array([app("logger"), $name], $arguments);
    }
}
