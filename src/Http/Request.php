<?php

namespace System\Http;

use System\Core\Snoop;
use System\Http\RequestData;

class Request
{
	private static $instance = null;
	private $app;

	private function __construct(Snoop $app)
	{
		$this->app = $app;
	}

	public static function load(Snoop $app)
	{
		if (self::$instance === null) {
			self::$instance = new self($app);
		}
		return self::$instance;
	}

	/**
	 * retourne uri revoyer par GET.
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
	 * Retourne la methode de la requete.
	 * @return string
	 */
	public function method()
	{
		return $_SERVER["REQUEST_METHOD"];
	}

    public static function body()
    {
        return RequestData::loader("POST");
    }

    public static function param()
    {
        return RequestData::loader("GET");
    }

    public static function files()
    {
        return RequestData::loader("FILES");
    }

	/**
	 * Verifie si on n'est dans le cas d'un requête XHR.
	 *
	 * @return boolean
	 */
	public function isXhr()
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
	public function clientAddress()
	{
		return $_SERVER["REMOTE_ADDR"];
	}
	/**
	 * clientPort, Retourne de port du client
	 *
	 * @return string
	 */
	public function clientPort()
	{
		return $_SERVER["REMOTE_PORT"];
	}

	/**
	 * Retourne la provenance de la requête courant.
	 *
	 * @return string
	 */
	public function requestReferer()
	{
		return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
	}


}
