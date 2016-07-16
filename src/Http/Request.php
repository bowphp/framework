<?php
namespace Bow\Http;

use StdClass;
use Bow\Support\Str;

/**
 * Class Request
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class Request
{
	/**
	 * Variable d'instance
	 *
	 * @static self
	 */
	private static $instance = null;

	/**
	 * Variable de paramètre issue de url définie par l'utilisateur
	 * e.g /users/:id . alors params serait params->id == une la value suivant /users/1
	 *
	 * @var object
	 */
	public static $params;

	/**
	 * Constructeur
	 */
	private function __construct()
	{
		static::$params = new StdClass();
	}

	/**
	 * Singletion loader
	 * @return null|self
	 */
	public static function configure()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * retourne uri envoyer par client.
	 *
	 * @return string
	 */
	public function uri()
	{
		if ($pos = strpos($_SERVER["REQUEST_URI"], "?")) {
			$uri = substr($_SERVER["REQUEST_URI"], 0, $pos);
		} else {
			$uri = $_SERVER["REQUEST_URI"];
		}

		return $uri;
	}

	/**
	 * retourne le nom host du serveur.
	 *
	 * @return string
	 */
	public function hostname()
	{
		return $_SERVER["HTTP_HOST"];
	}

	/**
	 * retourne url envoyé par client.
	 *
	 * @return string
	 */
	public function url()
	{
		return $this->origin() . $this->uri();
	}

	/**
	 * origin le nom du serveur + le scheme
	 *
	 * @return string
	 */
	public function origin()
	{
		if (!isset($_SERVER["REQUEST_SCHEME"])) {
			return "";
		}
		return $_SERVER["REQUEST_SCHEME"] . "://" . $this->hostname();
	}

	/**
	 * retourne path envoyé par client.
	 *
	 * @return string
	 */
	public function time()
	{
		return $_SESSION["REQUEST_TIME"];
	}

	/**
	 * Retourne la methode de la requete.
	 *
	 * @return string
	 */
	public function method()
	{
		$method = $_SERVER["REQUEST_METHOD"];

		if ($method == "POST") {
			if (array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER)) {
				if (in_array($_SERVER["HTTP_X_HTTP_METHOD"], ["PUT", "DELETE"])) {
					$method = $_SERVER["HTTP_X_HTTP_METHOD"];
				}
			}
		}

		return $method;
	}

	/**
	 * Si la réquête est de type POST
	 *
	 * @return bool
	 */
	public function isPost()
	{
		if ($this->method() == "POST") {
			return true;
		}

		return false;
	}

	/**
	 * Si la réquête est de type GET
	 *
	 * @return bool
	 */
	public function isGet()
	{
		if ($this->method() == "GET") {
			return true;
		}

		return false;
	}

	/**
	 * Si la réquête est de type PUT
	 *
	 * @return bool
	 */
	public function isPut()
	{
		if ($this->method() == "PUT" || $this->body()->get("_method", null) == "PUT") {
			return true;
		}

		return false;
	}

	/**
	 * Si la réquête est de type DELETE
	 *
	 * @return bool
	 */
	public function isDelete()
	{
		if ($this->method() == "DELETE" || $this->body()->get("_method", null) == "DELETE") {
			return true;
		}

		return false;
	}

	/**
	 * Charge la factory RequestData pour le POST
	 *
	 * @return RequestData
	 */
	public static function body()
	{
		return RequestData::configure("POST");
	}

	/**
	 * Charge la factory RequestData pour le GET
	 *
	 * @return RequestData
	 */
	public static function query()
	{
		return RequestData::configure("GET");
	}

	/**
	 * Charge la factory RequestData pour le FILES
	 *
	 * @return RequestData
	 */
	public static function files()
	{
		return RequestData::configure("FILES");
	}

	/**
	 * Change le factory RequestData pour tout les entrés PHP (GET, FILES, POST)
	 *
	 * @return RequestData
	 */
	public static function allInput()
	{
		return RequestData::configure("ALL");
	}

	/**
	 * Vérifie si on n'est dans le cas d'un requête AJAX.
	 *
	 * @return boolean
	 */
	public function isAjax()
	{
		if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
			$xhrObj = Str::lower($_SERVER["HTTP_X_REQUESTED_WITH"]);
			if ($xhrObj == "xmlhttprequest" || $xhrObj == "activexobject") {
				return true;
			}
		}

		return false;
	}

	/**
	 * clientAddress, L'address ip du client
	 *
	 * @return string
	 */
	public function address()
	{
		return $_SERVER["REMOTE_ADDR"];
	}
	/**
	 * clientPort, Retourne de port du client
	 *
	 * @return string
	 */
	public function port()
	{
		return $_SERVER["REMOTE_PORT"];
	}

	/**
	 * Retourne la provenance de la requête courante.
	 *
	 * @return string
	 */
	public function referer()
	{
		return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "/";
	}

	/**
	 * retourne la langue de la requête.
	 *
	 * @return string|null
	 */
	public function language()
	{
		return Str::slice($this->locale(), 0, 2);
	}

	/**
	 * retourne la localte de la requête.
	 *
	 * la locale c'est langue original du client
	 * e.g fr => locale = fr_FR // français de france
	 * e.g en => locale [ en_US, en_EN]
	 *
	 * @return string|null
	 */
	public function locale()
	{
		$local = "";

		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
			$tmp = explode(";", $_SERVER["HTTP_ACCEPT_LANGUAGE"])[0];
			preg_match("/^([a-z]+-?_?[a-z]+)/i", $tmp, $match);
			$local = end($match);
		}

		return $local;
	}

	/**
	 * le protocol de la requête.
	 *
	 * @return mixed
	 */
	public function protocol()
	{
		return $_SERVER["SERVER_PROTOCOL"];
	}
}