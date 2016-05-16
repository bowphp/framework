<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use Bow\Support\Str;
use Bow\Support\Flash;
use Bow\Support\Security;
use InvalidArgumentException;
use Bow\Interfaces\CollectionAccessStatic;

class Session implements CollectionAccessStatic
{

	/**
	 * Session starteur.
	 */
	private static function start()
	{
		if (PHP_SESSION_ACTIVE != session_status()) {
            session_name("BOWSESSID");
            if (!isset($_COOKIE["BOWSESSID"])) {
                session_id(hash("sha256", Security::encrypt(Str::repeat(Security::generateTokenCsrf(), 2))));
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
            if (!in_array($key, ["bow.flash", "bow.event", "bow.csrf"])) {
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
	public static function get($key = null, $default = null)
	{
		static::start();

		if ($key !== null) {
			if (static::has($key)) {
				return $_SESSION[$key];
			} else {
				return $default;
			}
		}

		return self::filter();
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
	 * @return static
	 */
	public static function add($key, $data, $next = false)
	{
		static::start();

		if (!is_string($key)) {
			throw new InvalidArgumentException("La clé doit être un chaine de caractère.", E_ERROR);
		}

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

		return self::class;
	}

	/**
     * remove, supprime une entrée dans la colléction
	 *
	 * @param string $key La clé de l'élément a supprimé
	 *
	 * @return void
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
    public static function flash($key, $message = null)
    {
        if (!static::has("bow.flash")) {
            $_SESSION["bow.flash"] = new Flash();
        }

		if (!in_array($key, ["error", "danger", "warning", "warn", "info", "success"])) {
			throw new \ErrorException("$key n'est pas valide.");
		}

        if ($key === "info") {
            $key = "information";
        }

        if ($key === "warn") {
            $key = "warning";
        }

        $flashMessage = null;

        if ($message === null) {
            $flashMessage = $_SESSION["bow.flash"]->$key();
            self::reFlash();
        } else {
            $_SESSION["bow.flash"]->$key($message);
        }

        return $flashMessage;
    }

    /**
     * reFlash
     */
    public static function reFlash()
    {
        unset($_SESSION["bow.flash"]);
    }

	/**
	 * clear, permet de vider le cache sauf csrf|bow.flash
	 */
	public static function clear()
	{
		self::start();

		foreach($_SESSION as $key => $value){
            if (!in_array($key, ["bow.csrf", "bow.flash", "bow.event"])) {
                unset($_SESSION[$key]);
            }
        }
	}

    /**
     * clearFull, permet vider le cache de session
     */
    public static function destroy()
    {
        static::start();
        session_unset();
        session_destroy();
    }
}
