<?php
/**
 * Created by PhpStorm.
 * User: papac
 * Date: 3/5/16
 * Time: 8:56 AM
 */

namespace Bow\Support;


class Flash
{
    private static $warning     = [];
    private static $information = [];
    private static $error       = [];
    private static $success     = [];


    private final function __construct(){}
    private final function __clone() {}

    public static function warning()
    {
        return static::toCollection("warning");
    }

    public static function information()
    {
        return static::toCollection("information");
    }

    public static function error()
    {
        return static::toCollection("success");
    }

    public static function success()
    {
        return static::toCollection("success");
    }

    /**
     * @param array $context
     */
    public static function addInfo(array $context)
    {
        static::addFash("information", $context);
    }

    /**
     * @param array $context
     */
    public static function addWarn(array $context)
    {
        static::addFash("warning", $context);
    }

    /**
     * @param array $context
     */
    public static function addError(array $context)
    {
        static::addFash("error", $context);
    }

    /**
     * @param array $context
     */
    public static function addSuccess(array $context)
    {
        static::addFash("success", $context);
    }

    /**
     * @param $level
     * @param array $context
     */
    private static function addFash($level, array $context)
    {
        foreach($context as $key => $value) {
            static::${$level}[$key] = $value;
        }
    }

    /**
     * @param string $level
     * @return Collection
     */
    private static function toCollection($level)
    {
        $data = static::${$level};
        $coll = new Collection();

        foreach($data as $key => $value) {
            $coll->add($key, $value);
        }

        return $coll;
    }
}