<?php

declare(strict_types=1);

namespace Bow\Console;

class Color
{
    /**
     * Red message
     *
     * @param string $message
     * @return string
     */
    public static function red(string $message): string
    {
        return "\033[0;31m$message\033[00m";
    }

    /**
     * Blue message
     *
     * @param string $message
     * @return string
     */
    public static function blue(string $message): string
    {
        return "\033[0;30m$message\033[00m";
    }

    /**
     * Yellow message
     *
     * @param string $message
     * @return string
     */
    public static function yellow(string $message): string
    {
        return "\033[0;33m$message\033[00m";
    }

    /**
     * Green message
     *
     * @param string $message
     * @return string
     */
    public static function green(string $message): string
    {
        return "\033[0;32m$message\033[00m";
    }

    /**
     * Red message with '[danger]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function danger(string $message): string
    {
        return static::red('[danger]').' '.$message;
    }

    /**
     * Blue message with '[info]' prefix
     *
     * @param  $message
     * @return string
     */
    public static function info(string $message): string
    {
        return static::blue('[info]').' '.$message;
    }

    /**
     * Yellow message with '[warning]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function warning(string $message): string
    {
        return static::yellow('[warning]').' '.$message;
    }

    /**
     * Greean message with '[success]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function success(string $message): string
    {
        return static::green('[success]').' '.$message;
    }
}
