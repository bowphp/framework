<?php

namespace Snoop\Support;


use InvalidArgumentException;
use Snoop\Interfaces\CollectionAccessStatic;


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
		
		if (is_string($key)) {
		
			return static::has($key) ? $_SESSION[$key] : false;
		
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
	 */
	public static function add($key, $data, $next = null) {
		
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
	 * 
	 * @return self
	 */
	public static function remove($key)
	{
		unset($_SESSION[$key]);

		return $this;
	}

	/**
	 * disconnect, permet vider le cache de session
	 */
	public static function stop()
	{
		
		self::start();
		session_destroy();
		session_unset();

	}
}
