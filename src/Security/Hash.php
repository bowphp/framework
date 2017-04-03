<?php
namespace Bow\Security;

class Hash
{
    /**
     * Permet de hasher une value et quand le hash a échoué
     * elle rétourne false.
     *
     * @param string $value
     * @return bool|string
     */
    public static function make($value)
    {
        return password_hash($value, PASSWORD_BCRYPT, [
            'cast' => 12
        ]);
    }

    /**
     * Permet de verifier le hash par apport a une value
     *
     * @param string $value
     * @param string $hash
     * @return bool
     */
    public static function check($value, $hash)
    {
        return password_verify($value, $hash);
    }
}