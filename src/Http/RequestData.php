<?php

namespace Bow\Http;

use Closure;
use ErrorException;
use Bow\Interfaces\CollectionAccess;

class RequestData implements CollectionAccess
{
	
	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @static self
	 */
	private static $instance = null;

	/**
	 * @static string
	 */
	private static $last_method = '';

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

		static::$last_method = $method;
	}

	/**
	 * Fonction magic __clone en <<private>>
	 */
	private function __clone(){}

	/**
	 * Factory permettant de charger les différentes colléctions
	 *
	 * @param $method
	 * 
	 * @return RequestData
	 */
	public static function configure($method)
	{
		if ($method == static::$last_method) {
			return static::$instance;
		} else {
			return static::$instance = new self($method);
		}
	}

	/**
	 * has, vérifie l'existance d'une clé dans la colléction
	 *
	 * @param string $key
	 * 
	 * @return boolean
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * isEmpty, vérifie si une collection est vide.
	 *
	 *	@return boolean
	 */
	public function isEmpty()
	{
		return empty($this->data);
	}

	/**
	 * get, permet de récupérer une valeur ou la colléction de valeur.
	 *
	 * @param string $key=null
	 * 
	 * @return mixed
	 */
	public function get($key = null)
	{
		if (!is_null($key)) {
			return $this->has($key) ? $this->data[$key] : false;
		}

		return $this->data;
	}

	/**
	 * remove, supprime une entrée dans la colléction
	 *
	 * @param string $key
	 * 
	 * @return RequestData
	 */
	public function remove($key)
	{
		unset($this->data[$key]);
		
		return $this;
	}

	/**
	 * add, ajoute une entrée dans la colléction
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param bool $next
	 * 
	 * @return RequestData
	 */
	public function add($key, $data, $next = false)
	{
		if($this->has($key)) {
			if ($next) {
				array_push($this->data[$key], $data);
			} else {
				$this->data[$key] = $data;
			}
		} else {
			$this->data[$key] = $data;
		}
		
		return $this;
	}

	/**
	 * set, modifie une entrée dans la colléction
	 *
	 * @param string $key
	 * @param mixed $value
	 * 
	 * @throws ErrorException
	 * 
	 * @return RequestData
	 */
	public function set($key, $value)
	{
		if ($this->has($key)) {
			$this->data[$key] = $value;
		} else {
			throw new ErrorException("Clé non définie", E_NOTICE);
		}
		
		return $this;
	}

	/**
	 * each, parcourir les entrées de la colléction
	 *
	 * @param Closure $cb
	 * 
	 * @return mixed
	 */
	public function each(Closure $cb)
	{
		if ($this->isEmpty()) {
			return call_user_func_array($cb, [null, null]);
		}

		foreach($this->data as $key => $value) {
			call_user_func_array($cb, [$key, $value]);
		}
	}
}
