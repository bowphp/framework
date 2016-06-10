<?php
namespace Bow\Http;

use Closure;
use ErrorException;
use Bow\Support\Collection;
use Bow\Interfaces\CollectionAccess;

/**
 * Class RequestData
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
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
	 * @param string $method
	 */
	private function __construct($method)
	{
		if ($method == "GET") {
			$this->data = $_GET;
		} else if ($method == "POST") {
			$this->data = $_POST;
		} else if ($method == "FILES") {
			$this->data = $_FILES;
		} else if ($method == "ALL") {
			$this->data = array_merge($_POST, $_FILES, $_GET);
		}

		static::$last_method = $method;
	}

	/**
	 * Fonction magic __clone en <<private>>
	 */
	private function __clone()
	{
	}

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
	 * @param bool $strict
	 *
	 * @return boolean
	 */
	public function has($key, $strict = false)
	{
		if ($strict) {
			return isset($this->data[$key]) && !empty($this->data[$key]);
		} else {
			return isset($this->data[$key]);
		}
	}

	/**
	 * isEmpty, vérifie si une collection est vide.
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return empty($this->data);
	}

	/**
	 * get, permet de récupérer une valeur ou la colléction de valeur.
	 *
	 * @param string $key =null
	 * @param mixed $default =false
	 * @return mixed
	 */
	public function get($key = null, $default = null)
	{
		if (!is_null($key)) {
			return $this->has($key) ? $this->data[$key] : $default;
		}

		return $this->data;
	}

	/**
	 * get, permet de récupérer une valeur ou la colléction de valeur.
	 *
	 * @return mixed
	 */
	public function getWithOut()
	{
		$data = [];
		$keyWasDefine = func_get_args();
		foreach ($this->data as $key => $value) {
			if (!in_array($key, $keyWasDefine)) {
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * vérifie si le contenu de $this->data poccedent la $key n'est pas vide.
	 *
	 * @param string $key
	 * @param string $eqTo
	 *
	 * @return bool
	 */
	public function isValide($key, $eqTo = null)
	{
		$boolean = $this->has($key, true);

		if ($eqTo && $boolean) {
			$boolean = $boolean && preg_match("~$eqTo~", $this->get($key));
		}

		return $boolean;
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
		if ($this->has($key)) {
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
	 */
	public function each(Closure $cb)
	{
		if ($this->isEmpty()) {
			call_user_func_array($cb, [null, null]);
		} else {
			foreach ($this->data as $key => $value) {
				call_user_func_array($cb, [$value, $key]);
			}
		}
	}

	/**
	 * __get
	 *
	 * @param string $name Le nom de la variable
	 * @return null
	 */
	public function __get($name)
	{
		if ($this->has($name)) {
			return $this->data[$name];
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function toArray()
	{
		return $this->data;
	}

	/**
	 * Retourne une instance de la classe collection.
	 *
	 * @return Collection
	 */
	public function toCollection()
	{
		return new Collection($this->data);
	}

	/**
	 * __set
	 *
	 * @param string $name Le nom de la variable
	 * @param mixed $value La valeur a assigné
	 * @return null
	 */
	public function __set($name, $value)
	{
		$old = null;

		if ($this->has($name)) {
			$old = $this->data[$name];
			$this->data[$name] = $value;
		} else {
			$this->data[$name] = $value;
		}

		return $old;
	}
}
