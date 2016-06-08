<?php
namespace Bow\Support\Session;

use Bow\Support\Str;
use Bow\Support\Security;
use InvalidArgumentException;
use Bow\Interfaces\CollectionAccessStatic;

/**
 * Class Session
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Session implements CollectionAccessStatic
{

	/**
	 * Session starteur.
	 */
	private static function start()
	{
		if (PHP_SESSION_ACTIVE != session_status()) {
            session_name("SESSID");
            if (!isset($_COOKIE["SESSID"])) {
                session_id(hash("sha256", Security::encrypt(Str::repeat(Security::generateCsrfToken(), 2))));
            }
            session_start();
        }
	}

    /**
     * @return array
     */
    private static function filter()
    {
        $arr = [];

        foreach($_SESSION as $key => $value) {
            if (!in_array($key, ["bow.flash", "bow.event", "bow.csrf", "bow.cookie.secure"])) {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

	/**
     * has, vérifie l'existance une clé dans la colléction de session
	 * 
	 * @param string $key
	 * @param bool $strict
	 *
	 * @return boolean
	 */
	public static function has($key, $strict = false)
	{
        $isset = isset($_SESSION[$key]);

        if ($strict) {
            if ($isset) {
                $isset = $isset && !empty($_SESSION[$key]);
            }
        }

		return $isset;
	}

	/**
     * isEmpty, vérifie si une colléction est vide.
	 * 
	 *	@return boolean
	 */
	public static function isEmpty()
	{
		return empty(self::filter());
	}

	/**
     * get, permet de récupérer une valeur ou la colléction de valeur.
	 * 
	 * @param string $key=null
	 * @param mixed $default
	 * 
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		static::start();

        if (static::has($key)) {
            return $_SESSION[$key];
        }

        if (isset($_SESSION["bow.flash"][$key])) {
            $flash = $_SESSION["bow.flash"][$key];
            static::reFlash($key);
            return $flash;
        }

        return $default;
	}

	/**
     * add, ajoute une entrée dans la colléction
	 *
	 * @param string|int $key La clé de la donnée à ajouter
	 * @param mixed $data La donnée à ajouter
	 * @param boolean $next Elle permet si elle est a true d'ajouter la donnée si la clé existe
     *                      Dans un tableau
	 *
	 * @throws InvalidArgumentException
	 * @return null
	 */
	public static function add($key, $data, $next = false)
	{
		static::start();

		if ($next === true) {
			if (static::has($key)) {
                if (!is_array($_SESSION[$key])) {
                    $_SESSION[$key] = [$_SESSION[$key]];
                }
                array_push($_SESSION[$key], $data);
			} else {
				$_SESSION[$key] = $data;
			}
		} else {
			$_SESSION[$key] = $data;
		}
	}

	/**
     * remove, supprime une entrée dans la colléction
	 *
	 * @param string $key La clé de l'élément a supprimé
	 *
	 * @return null
	 */
	public static function remove($key)
	{
        self::start();
		unset($_SESSION[$key]);
	}

    /**
     * set
	 *
     * @param string $key
     * @param mixed $value
	 *
     * @return mixed
     */
	public static function set($key, $value)
	{
        $old = null;

        if (static::has($key)) {
            $old = $_SESSION[$key];
            $_SESSION[$key] = $value;
        }

        return $old;
	}

    /**
     * flash
     *
     * @param $key
     * @param null $message
	 *
     * @throws \ErrorException
	 *
     * @return mixed
     */
    public static function flash($key, $message)
    {
        if (!static::has("bow.flash")) {
            $_SESSION["bow.flash"] = [];
        }
        $_SESSION["bow.flash"][$key] = $message;
    }

    /**
     * Retourne la liste des données de la session sous forme de tableau.
     *
     * @return array
     */
    public static function toArray()
    {
        return self::filter();
    }

    /**
     * reFlash
     * @param string $key
     */
    private static function reFlash($key)
    {
        unset($_SESSION["bow.flash"][$key]);
    }

	/**
	 * clear, permet de vider le cache sauf csrf|bow.flash
	 */
	public static function clear()
	{
		self::start();

		foreach(self::filter() as $key => $value){
            unset($_SESSION[$key]);
        }
	}
}
