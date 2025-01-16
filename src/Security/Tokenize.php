<?php

declare(strict_types=1);

namespace Bow\Security;

use Bow\Session\Exception\SessionException;
use Bow\Session\Session;
use Bow\Support\Str;

class Tokenize
{
    /**
     * The token expiration time
     *
     * @static int
     */
    private static int $expire_at;

    /**
     * Csrf token creator
     *
     * @param int|null $time
     * @return bool
     * @throws SessionException
     */
    public static function makeCsrfToken(?int $time = null): bool
    {
        if (Session::getInstance()->has('__bow.csrf', true)) {
            return true;
        }

        if (is_int($time)) {
            static::$expire_at = $time;
        }

        $token = static::make();

        Session::getInstance()->add('__bow.csrf', [
            'token' => $token,
            'expire_at' => time() + static::$expire_at,
            'field' => '<input type="hidden" name="_token" value="' . $token . '"/>'
        ]);

        Session::getInstance()->add('_token', $token);

        return true;
    }

    /**
     * GGenerate an encrypted key
     *
     * @return string
     */
    public static function make(): string
    {
        $salt = date('Y-m-d H:i:s', time() - 10000) . uniqid((string) rand(), true);

        $token = base64_encode(base64_encode(openssl_random_pseudo_bytes(6)) . $salt);

        return Str::slice(hash('sha256', $token), 1, 62);
    }

    /**
     * Get a csrf token generate
     *
     * @param int|null $time
     * @return ?array
     * @throws SessionException
     */
    public static function csrf(int $time = null): ?array
    {
        static::makeCsrfToken($time);

        return Session::getInstance()->get('__bow.csrf');
    }

    /**
     * Check if the token expires
     *
     * @param int|null $time
     * @return bool
     * @throws SessionException
     */
    public static function csrfExpired(int $time = null): bool
    {
        if (Session::getInstance()->has('__bow.csrf')) {
            return false;
        }

        if ($time === null) {
            $time = time();
        }

        $csrf = Session::getInstance()->get('__bow.csrf');

        if ($csrf['expire_at'] >= (int) $time) {
            return true;
        }

        return false;
    }

    /**
     * Check if csrf token is valid
     *
     * @param string $token
     * @param bool $strict
     * @return bool
     * @throws SessionException
     */
    public static function verify(string $token, bool $strict = false): bool
    {
        if (!Session::getInstance()->has('__bow.csrf')) {
            return false;
        }

        $csrf = Session::getInstance()->get('__bow.csrf');

        if ($token !== $csrf['token']) {
            return false;
        }

        $status = true;

        if ($strict) {
            $status = static::CsrfExpired(time());
        }

        return $status;
    }

    /**
     * Destroy the token
     *
     * @return void
     * @throws SessionException
     */
    public static function clear(): void
    {
        Session::getInstance()->remove('__bow.csrf');

        Session::getInstance()->remove('_token');
    }
}
