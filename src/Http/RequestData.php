<?php

namespace System\Http;

use System\Interfaces\CollectionAccess;

class RequestData implements CollectionAccess
{
	private $data;
	private static $instance = null;

	private function __construct($method)
	{
		if ($method == "GET") {
			$this->data = $_GET;
		} else if ($method == "POST") {
			$this->data = $_POST;
		} else if ($method == "FILES") {
			$this->data = $_FILES;
		}
	}

	private function __clone(){}

	public static function loader($method) {
		if (self::$instance === null) {
			self::$instance = new self($method);
		}
		return self::$instance;
	}
	/**
	 * isKey, verifie l'existance d'un
	 * cle dans le table de session
	 * @param string $key
	 * @return boolean
	 */
	public function isKey($key) {
		return isset($this->get()[$key]) && !empty($this->get()[$key]);
	}

	/**
	 * filessessionIsEmpty
	 *	@return boolean
	 */
	public function IsEmpty()
	{
		return empty($this->data);
	}

	/**
	 * session, permet de manipuler le donnee
	 * de session.
	 * permet de recuperer d'une valeur ou
	 * la collection de valeur.
	 * @param string $key=null
	 * @return mixed
	 */
	public function get($key = null) {
		if (is_string($key)) {
			return $this->isKey($key) ? $this->data[$key] : false;
		}
		return $this->data;
	}

	/**
	 * removeSession, supprime un entree dans la
	 * table de session.
	 * @param string $key
	 * @return self
	 */
	public function remove($key)
	{
		unset($this->data[$key]);
		return $this;
	}

}
