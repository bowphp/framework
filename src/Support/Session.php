<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

use InvalidArgumentException;
use Bow\Interfaces\CollectionAccessStatic;

class Session implements CollectionAccessStatic
{

	/**
	 * Session starteur.
	 */
	public static function start()
	{
		if (PHP_SESSION_ACTIVE != session_status()) {
			session_start();
		}
	}

	/**
     * has, vérifie l'existance une clé dans la colléction de session
	 * 
	 * @param string $key
	 * 
	 * @return boolean
	 */
	public static function has($key)
	{
		return isset(static::get()[$key]) && !empty(static::get()[$key]);
	}

	/**
     * isEmpty, vérifie si une colléction est vide.
	 * 
	 *	@return boolean
	 */
	public static function IsEmpty()
	{
		return empty($_SESSION);
	}

	/**
     * get, permet de récupérer une valeur ou la colléction de valeur.
	 * 
	 * @param string $key=null
	 * 
	 * @return mixed
	 */
	public static function get($key = null)
	{
		static::start();

		if ($key !== null) {
			if (static::has($key)) {
				return $_SESSION[$key];
			} else {
				return null;
			}
		}

		return $_SESSION;
	}

	/**
     * add, ajoute une entrée dans la colléction
	 * 
	 * @param string|int $key
	 * @param mixed $data
	 * @param boolean $next=null
	 * 
	 * @throws InvalidArgumentException
	 * @return void
	 */
	public static function add($key, $data, $next = null)
	{
		static::start();

		if (!is_string($key)) {
			throw new InvalidArgumentException("La clé doit être un chaine.", E_ERROR);
		}

		if ($next === true) {
			if (static::has($key)) {
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
	 * @param string $key
	 * @return void
	 */
	public static function remove($key)
	{
		unset($_SESSION[$key]);
	}

    /**
     * set
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
	public static function set($key, $value)
	{
        $old = null;

        if (static::has($key)) {
            $old = $_SERVER[$key];
            $_SERVER[$key] = $value;
        }

        return $old;
	}

    /**
     * flash
     *
     * @param $key
     * @param null $message
     * @return mixed
     */
    public function fash($key, $message = null)
    {
        if (!static::has("application_flash")) {
            $_SERVER["application_flash"] = new Collection();
        }

        if ($message === null) {
            return $_SERVER["application_flash"]->get($key);
        } else {
            $_SERVER["application_flash"]->add($key, $message);
        }

        return null;
    }

    /**
     * reFlash
     */
    public function reFlash()
    {
        unset($_SERVER["application_flash"]);
    }

	/**
	 * clear, permet de vider le cache sauf csrf|application_flash
	 */
	public static function clear()
	{
		self::start();

		foreach($_SERVER as $key => $value){
            if ($key !== "csrf" || $key !== "application_flash") {
                unset($_SERVER[$key]);
            }
        }
	}

    /**
     * clearFull, permet vider le cache de session
     */
    public static function clearFull()
    {
        static::start();
        session_unset();
        session_destroy();
    }
}
