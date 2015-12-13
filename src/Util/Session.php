<?php

namespace System\Util\Session;
use System\Interface\CollectionAccess;

class Session implements CollectionAccess
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
	 * isKey, verifie l'existance d'un
	 * cle dans le table de session
	 * @param string $key
	 * @return boolean
	 */
	public static function isKey($key) {
		return isset($this->get()[$key]) && !empty($this->get()[$key]);
	}

	/**
	 * filessessionIsEmpty
	 *	@return boolean
	 */
	public static function IsEmpty()
	{
		return empty($_SESSION);
	}

	/**
	 * session, permet de manipuler le donnee
	 * de session.
	 * permet de recuperer d'une valeur ou
	 * la collection de valeur.
	 * @param string $key=null
	 * @return mixed
	 */
	public static function get($key = null) {
		$this->start();
		if (is_string($key)) {
			return $this->isKey($key) ? $_SESSION[$key] : false;
		}
		return $_SESSION;
	}

	/**
	 * addSession, permet d'ajout une value
	 * dans le tableau de session.
	 * @param string|int $key
	 * @param mixed $data
	 * @param boolean $next=null
	 * @throws \InvalidArgumentException
	 */
	public static function add($key, $data, $next = null) {
		$this->start();
		if (!is_string($key)) {
			throw new \InvalidArgumentException("La clé doit être un chaine.", E_ERROR);
		}
		if ($next === true) {
			if ($this->isKey($key)) {
				array_push($_SESSION[$key], $data);
			} else {
				$_SESSION[$key] = $data;
			}
		} else {
			$_SESSION[$key] = $data;
		}
	}

	/**
	 * removeSession, supprime un entree dans la
	 * table de session.
	 * @param string $key
	 * @return self
	 */
	public static function remove($key)
	{
		unset($_SESSION[$key]);
		return $this;
	}

	/**
	 * disconnect, permet vider le cache session
	 */
	public static function disconnect()
	{
		self::start();
		session_destroy();
		session_unset();
	}
}
