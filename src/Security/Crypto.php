<?php

namespace Bow\Security;

class Crypto
{
    /**
     * @var string
     */
    private static $key = '';

    /**
     * @param $key
     */
    public static function setKey($key)
    {
        static::$key = $key;
    }

    /**
     * crypt
     *
     * @param string $data les données a encrypté
     * @return string
     */
    public static function encrypt($data)
    {
        $layer = base64_encode(trim($data));
        $layer = base64_encode(static::$key.$layer);

        return $layer;
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
        $layer = base64_decode(trim($data));
        $layer = explode(static::$key, $layer)[0];
        return base64_decode($layer);
    }
}