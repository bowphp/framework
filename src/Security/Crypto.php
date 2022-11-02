<?php

declare(strict_types=1);

namespace Bow\Security;

use Bow\Support\Str;

class Crypto
{
    /**
     * The security key
     *
     * @var string
     */
    private static string $key;

    /**
     * The security cipher
     *
     * @var string
     */
    private static string $cipher = 'AES-256-CBC';

    /**
     * Set the key
     *
     * @param string $key
     * @param string $cipher
     */
    public static function setKey(string $key, ?string $cipher = null)
    {
        static::$key = $key;

        if (!is_null($cipher)) {
            static::$cipher = $cipher;
        }
    }

    /**
     * Encrypt data
     *
     * @param  string $data
     * @return string|bool
     */
    public static function encrypt(string $data): string|bool
    {
        $iv_size = openssl_cipher_iv_length(static::$cipher);

        $iv = Str::slice(sha1(static::$key), 0, $iv_size);

        return openssl_encrypt($data, static::$cipher, static::$key, 0, $iv);
    }

    /**
     * decrypt
     *
     * @param string $data
     *
     * @return string
     */
    public static function decrypt(string $data): string|bool
    {
        $iv_size = openssl_cipher_iv_length(static::$cipher);
        
        $iv = Str::slice(sha1(static::$key), 0, $iv_size);

        return openssl_decrypt($data, static::$cipher, static::$key, 0, $iv);
    }
}
