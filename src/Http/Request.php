<?php

namespace Bow\Http;

use StdClass;
use Bow\Core\Application;

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
	public $params;

	/**
	 * Constructeur
	 */
	private function __construct()
	{
		$this->params = new StdClass();
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
		return $this->origin() . $_SERVER["REQUEST_URI"];
	}

    /**
     * origin le nom du serveur + le scheme
     *
     * @return string
     */
    public function origin()
    {
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
		return $_SERVER["REQUEST_METHOD"];
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
	 * Vérifie si on n'est dans le cas d'un requête AJAX.
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
	 * Retourne la provenance de la requête courante.
	 *
	 * @return string|null
	 */
	public function referer()
	{
		return isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
	}
}