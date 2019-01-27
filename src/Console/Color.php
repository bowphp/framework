<?php

namespace Bow\Console;

class Color
{
    /**
     * Red message
     *
     * @param string $message
     * @return string
     */
    public static function red($message)
    {
        return "\033[0;31m$message\033[00m";
    }

    /**
     * Blue message
     *
     * @param string $message
     * @return string
     */
    public static function blue($message)
    {
        return "\033[0;30m$message\033[00m";
    }

    /**
     * Yellow message
     *
     * @param string $message
     * @return string
     */
    public static function yellow($message)
    {
        return "\033[0;33m$message\033[00m";
    }

    /**
     * Green message
     *
     * @param string $message
     * @return string
     */
    public static function green($message)
    {
        return "\033[0;32m$message\033[00m";
    }

    /**
     * Red message with '[danger]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function danger($message)
    {
        return static::red('[danger]').' '.$message;
    }

    /**
     * Blue message with '[info]' prefix
     *
     * @param  $message
     * @return string
     */
    public static function info($message)
    {
        return static::blue('[info]').' '.$message;
    }

    /**
     * Yellow message with '[warning]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function warning($message)
    {
        return static::yellow('[warning]').' '.$message;
    }

    /**
     * Greean message with '[success]' prefix
     *
     * @param string $message
     * @return string
     */
    public static function success($message)
    {
        return static::green('[success]').' '.$message;
    }
}
