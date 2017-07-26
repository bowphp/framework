<?php
namespace Bow\Security;

class Hash
{
    /**
     * Permet de hasher une value et quand le hash a échoué
     * elle rétourne false.
     *
     * @param string $value
     * @param int $cost
     * @return bool|string
     */
    public static function make($value, $cost = 10)
    {
        return password_hash($value, PASSWORD_BCRYPT, [
            'cast' => $cost
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
        if (strlen($hash) === 0) {
            return false;
        }

        return password_verify($value, $hash);
    }

    /**
     * Permet de rehacher une value.
     *
     * @param $hash
     * @param int $cost
     * @return bool
     */
    public function needsRehash($hash, $cost = 10)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => $cost,
        ]);
    }
}