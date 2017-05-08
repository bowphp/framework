<?php
namespace Bow\Support\Console;

class Color
{
    /**
     * @param $message
     * @return string
     */
    public static function red($message)
    {
        return "\033[0;31m$message\033[00m";
    }

    /**
     * @param $message
     * @return string
     */
    public static function blue($message)
    {
        return "\033[0;30m$message\033[00m";
    }

    /**
     * @param $message
     * @return string
     */
    public static function yellow($message)
    {
        return "\033[0;33m$message\033[00m";
    }

    /**
     * @param $message
     * @return string
     */
    public static function green($message)
    {
        return "\033[0;32m$message\033[00m";
    }

    /**
     * @param $message
     * @return string
     */
    public static function danger($message)
    {
        return static::red('[danger]').' '.$message;
    }

    /**
     * @param $message
     * @return string
     */
    public static function info($message)
    {
        return static::blue('[info]').' '.$message;
    }

    /**
     * @param $message
     * @return string
     */
    public static function warning($message)
    {
        return static::yellow('[warning]').' '.$message;
    }

    /**
     * @param $message
     * @return string
     */
    public static function success($message)
    {
        return static::danger('[sucess]').' '.$message;
    }
}