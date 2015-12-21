<?php

namespace System\Http;

use StdClass;
use System\Core\Application;

class Request
{
	/**
	 * Variable d'instance
	 * 
	 * @var null
	 */
	private static $instance = null;
	
	/**
	 * Variable d'application
	 * 
	 * @var Application
	 */
	private $app;

	/**
	 * Variable de paramètre 
	 * 
	 * @var object
	 */
	public $params;

	/**
	 * Constructeur
	 * 
	 * @param Application $app
	 */
	private function __construct(Application $app)
	{
		$this->app = $app;
		$this->params = new StdClass();
	}

	/**
	 * Singletion loader
	 * 
	 * @param Application $app
	 * @return null|self
	 */
	public static function load(Application $app)
	{
		if (self::$instance === null) {
			self::$instance = new self($app);
		}

		return self::$instance;
	}

	/**
	 * retourne uri envoyer par client.
	 *
	 * @param string $path=""
	 * @return string
	 */
	public function uri($path = "")
	{
		if ($pos = strpos($_SERVER["REQUEST_URI"], "?")) {
			$uri = substr($_SERVER["REQUEST_URI"], 0, $pos);
		} else {
			$uri = $_SERVER["REQUEST_URI"];
		}

		return str_replace($path, "", $uri);
	}


	/**
	 * retourne path envoyer par client.
	 *
	 * @param string $path=""
	 * @return string
	 */
	public function path($path = "")
	{
		$uri = str_replace($_SERVER["HTTP_HOST"], "", $this->uri());

		return str_replace($path, "", $uri);
	}

	/**
	 * Retourne la methode de la requete.
	 *
	 * @return string
	 */
	public function method()
	{
		return $_SERVER["REQUEST_METHOD"];
	}

	/**
	 * Charge la factory RequestData pour le POST
	 *
	 * @return RequestData
	 */
    public static function body()
    {
        return RequestData::loader("POST");
    }

	/**
	 * Charge la factory RequestData pour le GET
	 *
	 * @return RequestData
	 */
    public static function query()
    {
        return RequestData::loader("GET");
    }

	/**
	 * Charge la factory RequestData pour le FILES
	 *
	 * @return RequestData
	 */
    public static function files()
    {
        return RequestData::loader("FILES");
    }

	/**
	 * Verifie si on n'est dans le cas d'un requête XHR.
	 *
	 * @return boolean
	 */
	public function ajax()
	{
		if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
			$xhrObj = strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]);
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
	 * Retourne la provenance de la requête courant.
	 *
	 * @return string
	 */
	public function referer()
	{
		return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
	}

}
