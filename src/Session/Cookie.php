<?php
namespace Bow\Session;

use Bow\Security\Crypto;

/**
 * Class Cookie
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Cookie
{
    /**
     * @var array
     */
    private static $isDecrypt = [];

    /**
     * @access private
     */
    final private function __clone()
    {
    }

    /**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @param bool   $strict
     *
     * @return boolean
     */
    public static function has($key, $strict = false)
    {
        $isset = isset($_COOKIE[$key]);

        if ($strict) {
            if ($isset) {
                $isset = $isset && !empty($_COOKIE[$key]);
            }
        }

        return $isset;
    }

    /**
     * isEmpty, vérifie si une colléction est vide.
     *
     * @return boolean
     */
    public static function isEmpty()
    {
        return empty($_COOKIE);
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur de cookie.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (static::has($key)) {
            return Crypto::decrypt($_COOKIE[$key]);
        }

        if (is_callable($default)) {
            return $default();
        }

        return  $default;
    }

    /**
     * Retourne tout les valeurs COOKIE
     *
     * @return mixed
     */
    public static function all()
    {
        foreach ($_COOKIE as $key => $value) {
            $_COOKIE[$key] = Crypto::decrypt($value);
        }

        return $_COOKIE;
    }

    /**
     * add, permet d'ajouter une value dans le tableau de cookie.
     *
     * @param string|int $key,     La clé du cookie
     * @param mixed      $data     La donnée a associée
     * @param int        $expirate Le temps de vie du cookie
     * @param string     $path     Le path de reconnaissance
     * @param string     $domain   Le domaine sur lequel sera envoyé le cookie
     * @param bool       $secure   Définie la sécurité
     * @param bool       $http     Définie si c'est seulement le protocole http
     *
     * @return bool
     */
    public static function add($key, $data = null, $expirate = 3600, $path = null, $domain = null, $secure = false, $http = true)
    {
        if ($data !== null) {
            $data = Crypto::encrypt($data);
        }

        return setcookie($key, $data, time() + $expirate, $path, $domain, $secure, $http);
    }

    /**
     * remove, supprime une entrée dans la table
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function remove($key)
    {
        $old = null;

        if (!static::has($key)) {
            return $old;
        }

        if (!static::$isDecrypt[$key]) {
            $old = Crypto::decrypt($_COOKIE[$key]);
            unset(static::$isDecrypt[$key]);
        }

        static::add($key, null, -1000);
        unset($_COOKIE[$key]);

        return $old;
    }
}
