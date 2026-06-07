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
     * @param  string $key
     * @param  mixed  $default
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
     * @param  string $key
     * @param  bool   $strict
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
     * @param  string $key
     * @return string|bool|null
     */
    public static function remove(string $key): string|bool|null
    {
        $old = null;

        if (!static::has($key)) {
            return null;
        }

        if (!(static::$is_decrypt[$key] ?? false)) {
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
     * @param  int|string $key
     * @param  mixed      $data
     * @param  int        $expiration
     * @return bool
     */
    public static function set(
        int|string $key,
        mixed $data,
        int $expiration = 3600,
    ): bool {
        $data = Crypto::encrypt(json_encode($data));

        return setcookie($key, $data, static::options($expiration));
    }

    /**
     * Build the setcookie() options array from the session config.
     *
     * Every value is coerced to its declared type. config('session.domain') is
     * null when SESSION_DOMAIN is unset; passing null straight to setcookie()
     * is deprecated on PHP 8.x and a fatal TypeError on PHP 9, so cast here.
     *
     * @param  int $expiration
     * @return array
     */
    private static function options(int $expiration): array
    {
        // config() with a second argument is a setter, not a getter-with-default,
        // so read each value first and apply the fallback in PHP.
        return [
            'expires'  => time() + $expiration,
            'path'     => (string) (config('session.path') ?? '/'),
            'domain'   => (string) (config('session.domain') ?? ''),
            'secure'   => (bool) config('session.secure'),
            'httponly' => (bool) (config('session.httponly') ?? true),
            'samesite' => (string) (config('session.samesite') ?? 'Lax'),
        ];
    }
}
