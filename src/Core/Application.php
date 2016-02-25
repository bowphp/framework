<?php

/**
 * @author DIAGNOSTIC sarl, <info@diagnostic-ci.com>
 * 
 * Create and maintener by diagnostic developpers teams:
 * 
 * @author Etchien Boa <geekroot9@gmail.com>
 * @author Dakia Franck <dakiafranck@gmail.com>
 * 
 * @package Bow\Core
 */

namespace Bow\Core;


use Bow\Support\Util;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Support\Logger;
use InvalidArgumentException;


class Application
{
	/**
	 * Définition de contrainte sur un route.
	 *
	 * @var array
	 */ 
	private $with = [];

	/**
	 * Branchement global sur un liste de route
	 * 
	 * @var string
	 */
	private $branch = "";

	/**
	 * @var string
	 */
	private $specialMethod = null;

	/**
	 * Répresente la racine de l'application
	 *
	 * @var string
	 */
	private $root = "";
	
	/**
	 * Fonction lancer en cas d'erreur.
	 * 
	 * @var null|callable
	 */
	private $error404 = null;

	/**
	 * Method Http courrante.
	 * 
	 * @var string
	 */
	private $currentMethod = "";
	/**
	 * Enrégistre l'information la route courrante
	 * 
	 * @var string
	 */
	private $currentPath = "";

	/**
	 * Patter Singleton
	 * 
	 * @var self
	 */
	private static $inst = null;

	/**
	 * Collecteur de route.
	 *
	 * @var array
	 */
	private static $routes = [];

	/**
	 * @var Request
	 */
	private $req;

	/**
	 * @var AppConfiguration|null
	 */
	private $config = null;
	/**
	 * Private construction
	 *
	 * @param AppConfiguration $config
	 */
	private function __construct(AppConfiguration $config)
	{
		$this->req = $this->request()->method();
		$this->config = $config;
        $this->req = $this->request();
	}

	/**
	 * Private __clone
	 */
	private function __clone(){}

	/**
	 * Pattern Singleton.
	 * 
	 * @param AppConfiguration $config
	 * @return self
	 */
	public static function configure(AppConfiguration $config)
	{
		if (static::$inst === null) {
			static::$inst = new static($config);
		}

		return static::$inst;
	}

	/**
	 * mount, ajoute un branchement.
	 *
	 * @param string $branch
	 * @param callable $cb
	 * @throws ApplicationException
	 * @return self
	 */
	public function group($branch, $cb)
	{
		$this->branch = $branch;

		if (is_array($cb)) {
			Util::launchCallback($cb, $this->req, $this->config->getNamespace());
		} else {
			if (!is_callable($cb)) {
				throw new ApplicationException(__METHOD__ . "(): callback are not define", 1);
			}
			call_user_func_array($cb, [$this->req]);
		}

        $this->branch = "";

		return $this;
	}

	/**
	 * get, route de type GET
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return self|string
	 */
	public function get($path, $cb = null)
	{
		if ($cb === null) {
			$prop = $path;
			if (property_exists($this, $prop)) {
				return $this->$prop;
			}
		}
		
		return $this->routeLoader("GET", $this->branch . $path, $cb);
	}

	/**
	 * post, route de type POST
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function post($path, $cb)
	{
		$body = $this->req->body();

		if ($body->has("method")) {
			$this->specialMethod = $method = strtoupper($body->get("method"));
			if (in_array($method, ["DELETE", "PUT", "UPDATE"])) {
				$this->addHttpVerbe($method, $this->branch . $path, $cb);
			}
			return $this;
		}
		
		return $this->routeLoader("POST", $this->branch . $path, $cb);
	}

	/**
	 * any, route de tout type GET|POST|DELETE|PUT
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function any($path, $cb)
	{
        foreach(["post", "delete", "put", "update", "get"] as $func) {
            $this->$func($path, $cb);
        }

		return $this;
	}

	/**
	 * delete, route de tout type DELETE
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function delete($path, $cb)
	{
		return $this->addHttpVerbe("DELETE", $path, $cb);
	}

	/**
	 * put, route de tout type PUT
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function put($path, $cb)
	{
		return $this->addHttpVerbe("PUT", $path, $cb);
	}

	/**
	 * to404, Charge le fichier 404 en cas de non
	 * validite de la requete
	 *
	 * @param callable $cb
	 * @return self
	 */
	public function to404($cb)
	{
		$this->error404 = $cb;
		return $this;
	}

	/**
	 * any, route de tout type PUT
	 *
	 * @param array $methods
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function match(array $methods, $path, $cb)
	{
		foreach($methods as $method) {
			if ($this->req->method() === strtoupper($method)) {
				$this->routeLoader($path, $this->req->method(), $cb);
			}
		}

		return $this;
	}

	/**
	 * addHttpVerbe, permet d'ajouter les autres verbes http
	 * [PUT, DELETE, UPDATE, HEAD]
	 *
	 * @param string $method
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	private function addHttpVerbe($method, $path, $cb)
	{
		$body = $this->req->body();
		$flag = true;

		if ($body !== null) {
			if ($body->has("method")) {
				if ($body->get("method") === $method) {
					$this->routeLoader($this->req->method(), $this->branch . $path, $cb);
				}
				$flag = false;
			}
		}

		if ($flag) {
			$this->routeLoader($method, $this->branch . $path, $cb);
		}

		return $this;
	}

	/**
	 * routeLoader, lance le chargement d'une route.
	 *
	 * @param string $method
	 * @param string $path
	 * @param callable|array $cb
	 * @return self
	 */
	private function routeLoader($method, $path, $cb)
	{
		static::$routes[$method][] = new Route($path, $cb);

		$this->currentPath = $path;
		$this->currentMethod = $method;

		return $this;
	}

	/**
	 * Lance une personnalisation de route.
	 * 
	 * @param array $otherRule
	 * @return self
	 */
	public function where(array $otherRule)
	{
		if (empty($this->with)) {
			$this->with[$this->currentMethod] = [];
			$this->with[$this->currentMethod][$this->currentPath] = $otherRule;
		} else {
			if (array_key_exists($this->currentMethod, $this->with)) {
				$this->with[$this->currentMethod] = array_merge(
					$this->with[$this->currentMethod], 
					[$this->currentPath => $otherRule]
				);
			}
		}

		return $this;
	}

	/**
	 * Lanceur de l'application
	 * 
	 * @param callable|null $cb
	 * @return void
	 */
	public function run($cb = null)
	{
		$this->response()->setHeader("X-Powered-By", "Bow Framework");
		$error = true;

		if (is_callable($cb)) {
			call_user_func_array($cb, [$this->req]);
		}

		$this->branch = "";
		$method = $this->req->method();

		if ($method == "POST") {
			if ($this->specialMethod !== null) {
				$method = $this->specialMethod;
			}
		}

		if (isset(static::$routes[$method])) {
			foreach (static::$routes[$method] as $key => $route) {	

				if (isset($this->with[$method][$route->getPath()])) {
					$with = $this->with[$method][$route->getPath()];
				} else {
					$with = [];
				}
                // Lancement de la recherche de la method qui arrivée dans la requete
                // ensuite lancement de la verification de l'url de la requete
                // execution de la fonction associé à la route.
				if ($route->match($this->req->uri($this->config->getApproot()), $with)) {
					$this->currentPath = $route->getPath();
					$response = $route->call($this->req, $this->config->getNamespace());
					if (is_string($response)) {
						$this->response()->send($response);
					} else if (is_array($response) || is_object($response)) {
						$this->response()->json($response);
					}
					$error = false;
				}
			}
		}

        // Si la route n'est pas enrégistre alors on lance une erreur 404
		if ($error) {
			$this->response()->setCode(404);
			if (is_callable($this->error404)) {
				call_user_func($this->error404);
			}
		}

		return $error;
	}

	/**
	 * Set, permet de rédéfinir la configuartion
	 *
	 * @param string $key
	 * @param string $value
	 * @throws InvalidArgumentException
	 */
	public function set($key, $value)
	{
		if (in_array($key, ["view", "engine", "root"])) {
			switch ($key) {
				case "view":
					$method = "setViewpath";
					break;
				case "engine":
					$method = "setEngine";
					break;
				case "root":
					$method = "setApproot";
					break;
			}

			if (method_exists($this->config, $method)) {
				return $this->config->$method($value);
			}

		} else {
			throw new InvalidArgumentException("Le premier argument n'est pas un argument de configuration");
		}
	}

	/**
	 * response, retourne une instance de la classe Response
	 * 
	 * @return Response
	 */
	public function response()
	{
		return Response::configure($this->config);
	}

	/**
	 * request, retourne une instance de la classe Request
	 * 
	 * @return Request
	 */
	public function request()
	{
		return Request::configure();
	}

	/**
	 * __call fonction magic php
	 * 
	 * @param string $method
	 * @param array $param
	 * @throws ApplicationException
	 * @return mixed
	 */
	public function __call($method, $param)
	{
		if (method_exists($this->config, $method)) {
			return call_user_func_array([$this->config, $method], $param);
		} else {
			throw new ApplicationException("$method not exists.", 1);
		}
	}

	/**
	 * @return mixed
	 */
	public function url()
	{
		return $this->currentPath;
	}
}