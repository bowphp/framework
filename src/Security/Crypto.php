<?php

namespace Bow\Security;

class Crypto
{
    /**
     * crypt
     *
     * @param string $data les données a encrypté
     * @return string
     */
    public static function encrypt($data)
    {
        return base64_encode($data);
    }

    /**
     * decrypt
     *
     * @param string $encrypted_data les données a décrypté
     *
     * @return string
     */
    public static function decrypt($encrypted_data)
    {
        return Sanitize::make(base64_decode(trim($encrypted_data)));
    }
}