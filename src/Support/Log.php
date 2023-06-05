<?php

namespace Bow\Support;

/**
 * @method void error(string $message, array $context = [])
 * @method void info(string $message, array $context = [])
 * @method void warning(string $message, array $context = [])
 * @method void alert(string $message, array $context = [])
 * @method void critical(string $message, array $context = [])
 * @method void emergency(string $message, array $context = [])
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
