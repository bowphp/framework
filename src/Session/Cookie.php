<?php

declare(strict_types=1);

namespace Bow\Session;

use Bow\Security\Crypto;

class Cookie
{
    /**
     * The decrypted data collection
     *
     * @var array
     */
    private static array $is_decrypt = [];

    /**
     * Check for existence of a key in the session collection
     *
     * @param string $key
     * @param bool $strict
     * @return bool
     */
    public static function has($key, $strict = false)
    {
        $isset = isset($_COOKIE[$key]);

        if (!$strict) {
            return $isset;
        }

        if ($isset) {
            $isset = $isset && !empty($_COOKIE[$key]);
        }

        return $isset;
    }

    /**
     * Check if a collection is empty.
     *
     * @return bool
     */
    public static function isEmpty(): bool
    {
        return empty($_COOKIE);
    }

    /**
     * Allows you to retrieve a value or collection of cookie value.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::has($key)) {
            return Crypto::decrypt($_COOKIE[$key]);
        }

        if (is_callable($default)) {
            return $default();
        }

        return $default;
    }

    /**
     * Return all values of COOKIE
     *
     * @return array
     */
    public static function all(): array
    {
        foreach ($_COOKIE as $key => $value) {
            $_COOKIE[$key] = Crypto::decrypt($value);
        }

        return $_COOKIE;
    }

    /**
     * Add a value to the cookie table.
     *
     * @param string|int $key
     * @param mixed      $data
     * @param int        $expirate
     * @param string     $path
     * @param string     $domain
     * @param bool       $secure
     * @param bool       $http
     *
     * @return bool
     */
    public static function set(
        $key,
        $data,
        $expirate = 3600,
        $path = null,
        $domain = null,
        $secure = false,
        $http = true
    ) {
        $data = Crypto::encrypt($data);

        return setcookie(
            $key,
            $data,
            time() + $expirate,
            $path,
            $domain,
            $secure,
            $http
        );
    }

    /**
     * Delete an entry in the table
     *
     * @param string $key
     * @return mixed
     */
    public static function remove(string $key): mixed
    {
        $old = null;

        if (!static::has($key)) {
            return $old;
        }

        if (!static::$is_decrypt[$key]) {
            $old = Crypto::decrypt($_COOKIE[$key]);
            unset(static::$is_decrypt[$key]);
        }

        static::set($key, null, -1000);
        unset($_COOKIE[$key]);

        return $old;
    }
}
