<?php

/**
 * @author diagnostic sarl, <info@diagnostic-ci.com>
 * create and maintener by diagnostic developpers teams:
 * - Etchien Boa
 * - Dakia Franck
 * - Zokora Elvis
 * @+- 10/06/2015 fast web app building
 * @package Snoope
 */

namespace System\Core;

use System\Support\Logger;
use System\Database\DB;
use System\Support\Util;
use System\Http\Response;
use System\Http\Request;

class Snoop
{
	/***
	 * Liste des constances
	 * d'execution de Requete
	 * SQL. Pour le system de
	 * de base de donnee ultra
	 * minimalise de snoop.
	 */
	const SELECT = 1;
	const UPDATE = 2;
	const DELETE = 3;
	const INSERT = 4;

	/**
	 * Collecteur de route.
	 *
	 * @var array
	 */
	private static $routes = [];
	/**
	 * Definition de contrainte sur un route.
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
	 * Represente le chemin vers la vue.
	 * 
	 * @var null|string
	 */
	private $views = null;
	/**
	 * Systeme de template
	 *
	 * @var string|null
	 */
	private $engine = null;
	/**
	 * Repertoire de cache
	 * 
	 * @var string
	 */
	private $cache = null;
	/**
	 * Represente de la racine de l'application
	 *
	 * @var string
	 */
	private $root = "";
	/**
	 * Epresente le dossier public
	 *
	 * @var string
	 */
	private $public = "";
	/**
	 * Enregistre la route courante
	 *
	 * @var string
	 */
	private $currentRoot = "";
	/**
	 * Fonction lancer en cas d'erreur.
	 * 
	 * @var null|callable
	 */
	private $error404 = null;
	/**
	 * Patter Singleton
	 * 
	 * @var self
	 */
	private static $inst = null;

	private static $mail = null;

	private static $appname = null;

	private static $loglevel = "dev";

	private $logFileName = "";

	private $method = "";

	private $currentMethod = "";

	private $names = [];

	/**
	 * Private construction
	 */
	private function __construct($config)
	{
        if (isset($config->timezone)) {
            Util::settimezone($config->timezone);
        }
		static::$appname = $config->appname;
		$this->logDirecotoryName = $config->logDirecotoryName;
		$this->views = $config->views;
		$this->engine = $config->template;
		$this->cache = $config->cacheFolder;
		$this->names = $config->names;
        static::$loglevel = isset($config->loglevel) ? $config->loglevel : static::$loglevel;
        $this->method = $this->request()->method();

	}
	/**
	 * Private __clone
	 */
	private function __clone(){}

	/**
	 * Pattern Singleton.
	 * 
	 * 
	 * @return self
	 */
	public static function loader($config)
	{
		if (static::$inst === null) {
			static::$inst = new self($config);
		}
		return static::$inst;
	}

	/**
	 * Pattern singleton et factory.
	 * 
	 * 
	 * @param boolean $smtp=false
	 * @return Mail
	 */
	public static function mailFactory($smtp = false)
	{
		if (static::$mail === null) {
			if ($smtp === true) {
				static::$mail = SmtpMail::load();
			} else {
				static::$mail = Mail::load();
			}
		}
		return static::$mail;
	}
	/**
	 * mount, ajout un branchement.
	 * 
	 * 
	 * @param string $branchName
	 * @param callable|null $middelware
	 * @return self
	 */
	public function mount($branchName, $middelware = null)
	{
		if ($middelware !== null) {
			call_user_func($middelware, [$this->request(), $this->response()]);
		}
		$this->branch .= $branchName;
		return $this;
	}

	/**
	 * Unmount, détruit le branchement en cour.
	 * 
	 * 
	 * @return self
	 */
	public function unmount()
	{
		$this->branch = "";
		return $this;
	}

	/**
	 * get, route de type GET
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function get($path, $cb = null)
	{
		if ($cb == null) {
			$prop = $path;
			if (property_exists($this, $prop)) {
				return $this->$prop;
			}
		}
		$this->currentRoot = $this->branch . $path;
		return $this->routeLoader("GET", $this->currentRoot, $cb);
	}

	/**
	 * any, route de tout type GET|POST|DELETE|PUT
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function any($path, $cb)
	{
		$this->post($path, $cb)
		->delete($path, $cb)
		->put($path, $cb)
		->update($path, $cb)
		->get($path, $cb);
		return $this;
	}

	/**
	 * any, route de tout type DELETE
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function delete($path, $cb)
	{
		return $this->addHttpVerbe("_DELETE", $path, $cb);
	}

	/**
	 * any, route de tout type UPDATE
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function update($path, $cb)
	{
		return $this->addHttpVerbe("_UPDATE", $path, $cb);
	}

	/**
	 * any, route de tout type PUT
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function put($path, $cb)
	{
		return $this->addHttpVerbe("_PUT", $path, $cb);
	}

	/**
	 * any, route de tout type PUT
	 * 
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function head($path, $cb)
	{
		return $this->addHttpVerbe("_HEAD", $path, $cb);
	}

	/**
	 * to404, Charge le fichier 404 en cas de non
	 * validite de la requete
	 * 
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
	 * 
	 * @param callable $cb
	 * @return self
	 */
	public function match($match, $middleware, $cb)
	{
		if (in_array($this->method, $match)) {

		}
		$this->error404 = $cb;
		return $this;
	}

	/**
	 * addHttpVerbe, permet d'ajout les autres verbes https
	 * PUT, DELETE, UPDATE, HEAD
	 * 
	 * 
	 * @param string $method
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	private function addHttpVerbe($method, $path, $cb)
	{
		if ($this->isBodyKey("method")) {
			if ($this->body("method") === $method) {
				$this->routeLoader($this->method, $this->branch . $path, $cb);
			}
		}
		return $this;
	}

	/**
	 * post, route de type POST
	 *
	 * 
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function post($path, $cb)
	{
		if ($this->isBodyKey("method")) {
			return $this;
		}
		$this->currentRoot = $this->branch . $path;
		return $this->routeLoader("POST", $this->currentRoot, $cb);
	}

	/**
	 * routeLoader, lance le chargement d'une route.
	 * 
	 * 
	 * @param string $method
	 * @param string $path
	 * @param callable|array $cb
	 * @return self
	 */
	private function routeLoader($method, $path, $cb)
	{
		static::$routes[$method][] = new Route($path, $cb);
		$this->currentRoute = $path;
		$this->currentMethod = $method;
		return $this;
	}

	/**
	 * Lance une personnalistaion de route.
	 * 
	 * 
	 * @param array $otherRule
	 * @return \System\Snoop
	 */
	public function where(array $otherRule)
	{
		if (empty($this->with)) {
			$this->with[$this->currentMethod] = [];
			$this->with[$this->currentMethod][$this->currentRoute] = $otherRule;
		} else {
			$this->with[$this->currentMethod] = array_merge(
				$this->with[$this->currentMethod], 
				[$this->currentRoute => $otherRule]
			);
		}
		return $this;
	}

	/**
	 * Lanceur de l'application
	 * 
	 * @return void
	 */
	public function run()
	{
		$this->response()->setHeader("X-Powered-By", "Snoop Framework");
		$error = true;
		if (isset(static::$routes[$this->method])) {
			
			foreach (static::$routes[$this->method] as $key => $route) {	
				if (isset($this->with[$this->method][$route->getPath()])) {
					$with = $this->with[$this->method][$route->getPath()];
				} else {
					$with =  [];
				}
				if ($route->match($this->request()->uri($this->root), $with)) {

					$route->call($this->request(), $this->response(), $this->names);
					$error = false;
				}
			}

		} else {
			$error = false;
		}

		if ($error) {
			$this->response()->setCode(404);
			if ($this->error404 !== null && is_callable($this->error404)) {
				call_user_func($this->error404);
			}
			static::log("[404] route -" . $this->request()->uri() . "- non definie");
		}
	}

	/**
	 * Kill process
	 *
	 * 
	 * @param string $message=""
	 * @param int|bool $status
	 * @param bool $log=false
	 * @return void
	 */
	public function kill($message = "", $status = 200, $log = false)
	{
		if (is_bool($status) && $status == true) {
			$log = $status;
		} else {
			$this->response()->setCode($status);
		}
		if (!is_string($message)) {
			$message = null;
		}
		if ($log) {
			$this->log($message);
		} else {
			echo $message;
		}
		die();
	}

	/**
	 * Set, permet de rédéfinir la configuartion
	 * 
	 * 
	 * @param string $key
	 * @param string $value
	 * @throws \InvalidArgumentException
	 */
	public function set($key, $value)
	{
		if (in_array($key, ["views", "engine", "public", "root"])) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		} else {
			throw new InvalidArgumentException("Le premier argument n'est pas un argument de configuration");
		}
	}

	/**
	 * body, retourne les informations du POST ou une seule si un clé est
	 * passée paramètre
	 * 
	 * 
	 * @param string $key=null
	 * @return array
	 */
	public function body($key = null)
	{

		if ($key !== null) {
			return $this->isBodyKey($key) ? $_POST[$key] : false;
		}
		return $_POST;
	}

	/**
	 * isBodyKey, vérifie si de Snoop::body contient la clé definie.
	 * 
	 * 
	 * @param mixed $key
	 * @return mixed $key
	 */
	public function isBodyKey($key)
	{
		return isset($_POST[$key]) && !empty($_POST[$key]);
	}

	/**
	 * bodyIsEmpty, vérifie si le tableau $_POST est vide.
	 * 
	 * 
	 *	@return boolean
	 */
	public function bodyIsEmpty()
	{
		return empty($_POST);
	}

	/**
	 * Param, retourne les informations du GET ou une seule si un clé est
	 * 
	 * 
	 * passée paramètre
	 * @param string $key=null
	 * @return array
	 */
	public function param($key = null)
	{
		if ($key !== null) {
			return $this->isParamKey($key) ? $_GET[$key] : false;
		}
		return $_GET;
	}

	/**
	 * isParamKey, vérifie si de Snoop::param contient la cle definie.
	 * 
	 * 
	 * @param string|int $key
	 * @return mixed
	 */
	public function isParamKey($key)
	{
		return isset($_GET[$key]) && !empty($key);
	}

	/**
	 * paramIsEmpty, vérifie si le tableau $_GET est vide.
	 * 
	 * 
	 *	@return boolean
	 */
	public function paramIsEmpty()
	{
		return empty($_GET);
	}

	/**
	 * files, retourne les informations du $_FILES
	 * 
	 * 
	 * @param string|null $key
	 * @return mixed
	 */
	public function files($key = null)
	{
		if ($key !== null) {
			return isset($_FILES[$key]) ? (object) $_FILES[$key] : false;
		}
		return $_FILES;
	}

	/**
	 * isParamKey, vérifie si Snoop::files contient la clé définie.
	 * 
	 * 
	 * @param string|int $key
	 * @return mixed
	 */
	public function isFilesKey($key)
	{
		return isset($_FILES[$key]) && !empty($_FILES[$key]);
	}

	/**
	 * filesIsEmpty, vérifie si le tableau $_FILES est vide.
	 *	@return boolean
	 */
	public function filesIsEmpty()
	{
		return empty($_FILES);
	}

	/**
	 * currentRoot, retourne la route courante
	 * 
	 * 
	 * @return string
	 */
	public function currentRoot()
	{
		return $this->currentRoot;
	}

	/**
	 * Res, retourne une instance de Response
	 * 
	 * 
	 * @return \System\Response\Response
	 */
	private function response()
	{
		return Response::load($this);
	}

	/**
	 * Req, retourne une instance de Request
	 * 
	 * 
	 * @return \System\Request\Request
	 */
	private function request()
	{
		return Request::load($this);
	}

	/**
	 * Logeur d'erreur.
	 * 
	 * 
	 * @param string $message
	 */
	private function log($message)
	{
		$f_log = fopen($this->logDirecotoryName . "/error.log", "a+");
		if ($f_log != null) {
			fprintf($f_log, "[%s] - %s:%d: %s\n", date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], $_SERVER["REMOTE_PORT"], $message);
			fclose($f_log);
		}
	}

}
