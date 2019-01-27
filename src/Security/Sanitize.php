<?php

namespace Bow\Security;

class Sanitize
{
    /**
     * To clean the data
     *
     * @param mixed $data
     * @param bool  $secure
     *
     * @return mixed
     */
    public static function make($data, $secure = false)
    {
        // Recovery of the function at the lance.
        $method = $secure === true ? 'secure' : 'data';

        // Strict integer regex
        $rNum = '/^[0-9]+(\.[0-9]+)?$/';

        if (is_string($data)) {
            if (! preg_match($rNum, $data, $match)) {
                return static::$method($data);
            }

            if (count($match) == 2) {
                $data = (float) $data;
            } else {
                $data = (int) $data;
            }

            return $data;
        }

        if (is_numeric($data)) {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = static::make($value, $secure);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = static::make($value, $secure);
            }
            return $data;
        }

        return $data;
    }

    /**
     * Allows you to clean a string of characters
     *
     * @param  string $data
     * @return string
     */
    public static function data($data)
    {
        return stripslashes(stripslashes(trim($data)));
    }

    /**
     * Allows you to clean a string of characters
     *
     * @param  string $data
     * @return string
     */
    public static function secure($data)
    {
        return htmlspecialchars(addslashes(trim($data)));
    }
}
