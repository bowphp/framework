<?php

namespace Bow\Security;

class Hash
{
    /**
     * Allows to have a value and when the hash has failed it returns false.
     *
     * @param  string $value
     * @param  int    $cost
     * @return bool|string
     */
    public static function create($value, $cost = 10)
    {
        return password_hash($value, PASSWORD_BCRYPT, ['cast' => $cost]);
    }

    /**
     * Allows to have a value and when the hash has failed it returns false.
     *
     * @deprecated
     * @param  string $value
     * @param  int    $cost
     * @return bool|string
     */
    public static function make($value, $cost = 10)
    {
        return password_hash($value, PASSWORD_BCRYPT, ['cast' => $cost]);
    }

    /**
     * Allows you to check the hash by adding a value
     *
     * @param  string $value
     * @param  string $hash
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
     * Allows you to rehash a value.
     *
     * @param  $hash
     * @param  int  $cost
     * @return bool
     */
    public function needsRehash($hash, $cost = 10)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
