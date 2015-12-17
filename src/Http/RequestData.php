<?php

namespace System\Http;

use ErrorException;
use System\Interfaces\CollectionAccess;

class RequestData implements CollectionAccess
{
	private $data = [];
	private static $instance = null;

	/**
	 * Contructeur privé
	 *
	 * @param $method
	 */
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

	/**
	 * Fonction magic __clone en private
	 */
	private function __clone(){}

	/**
	 * Factory permettant de charger les différentes collections
	 *
	 * @param $method
	 * @return self
	 */
	public static function loader($method)
	{
		if (self::$instance === null) {
			self::$instance = new self($method);
		}
		return self::$instance;
	}

	/**
	 * isKey, verifie l'existance d'un clé dans la collection de session
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function isKey($key) {
		return isset($this->get()[$key]) && !empty($this->get()[$key]);
	}

	/**
	 * IsEmpty, vérifie si une collection est vide.
	 *
	 *	@return boolean
	 */
	public function IsEmpty()
	{
		return empty($this->data);
	}

	/**
	 * get, permet de recuperer d'une valeur ou la collection de valeur.
	 *
	 * @param string $key=null
	 * @return mixed
	 */
	public function get($key = null) {
		if (!is_null($key)) {
			return $this->isKey($key) ? $this->data[$key] : false;
		}
		return $this->data;
	}

	/**
	 * remove, supprime un entree dans la collection
	 *
	 * @param string $key
	 * @return self
	 */
	public function remove($key)
	{
		unset($this->data[$key]);
		return $this;
	}

	/**
	 * add, ajouté une entrée dans la collection
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param bool $next
	 * @return self
	 */
	public function add($key, $data, $next = false)
	{
		if($this->isKey($key)) {
			if ($next) {
				array_push($this->data[$key], $data);
			} else {
				$this->data[$key] = $data;
			}
		} else {
			$this->data[$key] = $data;
		}
	}

	/**
	 * set, modifier une entree dans la collection
	 *
	 * @param string $key
	 * @param mixed $value
	 * @throws ErrorException
	 * @return self
	 */
	public function set($key, $value)
	{
		if ($this->isKey($key)) {
			$this->data[$key] = $value;
			return $this;
		} else {
			throw new ErrorException("Clé non définie", E_NOTICE);
		}
	}

}
