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
     * @param mixed $default
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
     * Check for existence of a key in the session collection
     *
     * @param string $key
     * @param bool $strict
     * @return bool
     */
    public static function has(string $key, bool $strict = false): bool
    {
        $isset = isset($_COOKIE[$key]);

        if (!$strict) {
            return $isset;
        }

        if ($isset) {
            $isset = !empty($_COOKIE[$key]);
        }

        return $isset;
    }

    /**
     * Return all values of COOKIE
     *
     * @return array
     */
    public static function all(): array
    {
        foreach ($_COOKIE as $key => $value) {
            $_COOKIE[$key] = json_decode(Crypto::decrypt($value));
        }

        return $_COOKIE;
    }

    /**
     * Delete an entry in the table
     *
     * @param string $key
     * @return string|bool|null
     */
    public static function remove(string $key): string|bool|null
    {
        $old = null;

        if (!static::has($key)) {
            return null;
        }

        if (!static::$is_decrypt[$key]) {
            $old = Crypto::decrypt($_COOKIE[$key]);

            unset(static::$is_decrypt[$key]);
        }

        static::set($key, '', -1000);

        unset($_COOKIE[$key]);

        return $old;
    }

    /**
     * Add a value to the cookie table.
     *
     * @param int|string $key
     * @param mixed $data
     * @param int $expiration
     * @return bool
     */
    public static function set(
        int|string $key,
        mixed $data,
        int $expiration = 3600,
    ): bool {
        $data = Crypto::encrypt(json_encode($data));

        return setcookie(
            $key,
            $data,
            time() + $expiration,
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            config('session.httponly')
        );
    }
}
