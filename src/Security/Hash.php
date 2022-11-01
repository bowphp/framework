<?php

declare(strict_types=1);

namespace Bow\Security;

class Hash
{
    /**
     * Allows to have a value and when the hash has failed it returns false.
     *
     * @param  string $value
     * @return bool|string
     */
    public static function create($value)
    {
        [$hash_method, $options] = static::getHashConfig();

        return password_hash($value, $hash_method, $options);
    }

    /**
     * Allows to have a value and when the hash has failed it returns false.
     *
     * @param  string $value
     * @return bool|string
     */
    public static function make($value)
    {
        [$hash_method, $options] = static::getHashConfig();
        
        return password_hash($value, $hash_method, $options);
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
     * @return bool
     */
    public static function needsRehash($hash)
    {
        [$hash_method, $options] = static::getHashConfig();

        return password_needs_rehash($hash, $hash_method, $options);
    }

    /**
     * Get the hash configuration
     *
     * @return array
     */
    protected static function getHashConfig()
    {
        $hash_method = config('security.hash_method');
        $options = config('security.hash_options');

        if (is_null($hash_method) || $hash_method == PASSWORD_BCRYPT) {
            $hash_method = PASSWORD_BCRYPT;
        }

        return [$hash_method, $options];
    }
}
