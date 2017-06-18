<?php

namespace Bow\Security;

use Bow\Support\Str;
use function mcrypt_create_iv;
use function microtime;
use const null;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function sha1;
use const true;
use function var_dump;

class Crypto
{
    /**
     * @var string
     */
    private static $key = '';

    /**
     * @var string
     */
    private static $cipher = 'AES-256-CBC';

    /**
     * Crypto constructor
     *
     * @param string $key
     * @param string $cipher
     */
    public static function setKey($key, $cipher = null)
    {
        static::$key = $key;

        if ($cipher) {
            static::$cipher = $cipher;
        }
    }

    /**
     * crypt
     *
     * @param string $data les données a encrypté
     * @return string
     */
    public static function encrypt($data)
    {
        $iv_size = openssl_cipher_iv_length(static::$cipher);
        $iv = Str::slice(sha1(static::$key), 0, $iv_size);
        return openssl_encrypt($data, static::$cipher, static::$key, 0, $iv);
    }

    /**
     * decrypt
     *
     * @param string $data les données a décrypté
     *
     * @return string
     */
    public static function decrypt($data)
    {
        $iv_size = openssl_cipher_iv_length(static::$cipher);
        $iv = Str::slice(sha1(static::$key), 0, $iv_size);
        return openssl_decrypt($data, static::$cipher, static::$key, 0, $iv);
    }
}