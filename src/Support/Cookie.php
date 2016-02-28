<?php

namespace Bow\Support;

class Cookie
{
    /**
     * @var array
     */
    private static $isDecrypt = [];
    /**
     * @access private
     */
    private final function __construct() {}

    /**
     * @access private
     */
    private final function __clone() {}

    /**
     * has, vérifie l'existance une clé dans la colléction de session
     * 
     * @param string $key
     * 
     * @return boolean
     */
    public static function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    /**
     * isEmpty, vérifie si une colléction est vide.
     * 
     * @return boolean
     */
    public static function IsEmpty()
    {
        return empty($_COOKIE);
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur de cookie.
     *
     * @param string $key=null
     * 
     * @return mixed
     */
    public static function get($key = null)
    {
        if (static::has($key)) {
            if (! static::$isDecrypt[$key]) {
                static::$isDecrypt[$key] = true;
                return Security::decrypt($_COOKIE[$key]);
            }

            return  Security::decrypt($_COOKIE[$key]);
        }

        foreach($_COOKIE as $cookie_key => $value) {
            if (! static::$isDecrypt[$cookie_key]) {
                static::$isDecrypt[$cookie_key] = true;
                $_COOKIE[$cookie_key] = Security::decrypt($value);
            }
        }

        return $_COOKIE;
    }

    /**
     * add, permet d'ajouter une value dans le tableau de cookie.
     * 
     * @param string|int $key, la clé du cookie
     * @param mixed $data la donnée a associée
     * @param int $expirate le temps de vie du cookie
     * @param string $path le path de reconnaissance
     * @param string $domain le domaine sur lequel sera envoyé le cookie
     * @param bool $secure définie la sécurité
     * @param bool $http définie si c'est seulement le protocole http
     * @return bool
     */
    public static function add($key, $data = null, $expirate = 3600, $path = null, $domain = null, $secure = false, $http = true)
    {
        static::$isDecrypt[$key] = false;

        if ($data !== null) {
            $data = Security::encrypt($data);
        }

        return setcookie($key, $data, $expirate, $path, $domain, $secure, $http);
    }

    /**
     * remove, supprime une entrée dans la table
     * 
     * @param string $key
     * @return self
     */
    public static function remove($key)
    {
        $old = null;

        if (static::has($key)) {
            if (! static::$isDecrypt[$key]) {
                $old = Security::decrypt($_COOKIE[$key]);
                unset(static::$isDecrypt[$key]);
            }

            unset($_COOKIE[$key]);
        }

        return $old;
    }
}