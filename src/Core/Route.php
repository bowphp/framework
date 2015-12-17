<?php

/**
 * @author dakia Franck
 * @version 0.0.1
 */

namespace System\Core;

use System\Http\Response;
use System\Http\Request;
use System\Exception\RouterException;

Class Route
{
	/**
	 * Le callaback a lance si le url
	 * de la requete de matcher.
	 * @var $cb
	 */
	private $cb;
	/**
	 * Le chemin sur la route definir
	 * par l'utilisateur
	 *
	 * @var $path
	 */
	private $path;
	/**
	 * Liste de paramaters qui on matcher
	 *
	 * @var $match
	 */
	private $match;

	/**
	 * Régle supplementaire de validation
	 * d'url
	 *
	 * @var $with
	 */
	private $with;

	/**
	 * Liste de namespace.
	 *
	 * @var $with
	 */
	private $names = [];

	/**
	 * Contructeur
	 *
	 * @param string $path
	 * @param callable $cb
	 */
	public function __construct($path, $cb)
	{
		$this->cb = $cb;
		$this->path = $path;
		$this->match = [];
	}
	/**
	 * Retourne le chemin de la route current
	 *
	 * @var void
	 */ 
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * match, vérifie si le path de la REQUEST est conforme a celle
	 * definir par le routier
	 * @param string $url
     * @return bool.
	 */
	public function match($url, $with)
	{
		$this->with = $with;
		
		if (preg_match("~(.+)/$~", $url, $match)) {
			$url = end($match);
		}
		if (preg_match("~(.+)/$~", $this->path, $match)) {
			$this->path = end($match);
		}
		if (count(explode("/", $this->path)) != count(explode("/", $url))) {
			return false;
		}
        $path = "";
		if (empty($this->with)) {
			$path = preg_replace("~:\w+~", "([^\s]+)", $this->path);
		} else {
			if (preg_match_all("~:([\w]+)~", $this->path, $match)) {
                $tmpPath =  $this->path;
				foreach ($match[1] as $key => $value) {
                    if (array_key_exists($value, $this->with)) {
                        $tmpPath = preg_replace("~:$value~", "(" . $this->with[$value] . ")", $tmpPath);
                    }
                }
                if ($tmpPath !== $this->path) {
                    $path = $tmpPath;
                }
			}
			$this->with = [];
		}
		// Verifcation de url
		if (preg_match("~^$path$~", $url, $match)) {
			array_shift($match);
			$this->match = str_replace("/", "", $match);
			return true;
		}
		return false;
	}

	/**
	 * Fonction permettant de lancer les fonctions
	 * de rappel.
	 * 
	 * @param Request $req
	 * @param Response $res
	 * @param array $namespace
	 * @return mixed
	 */
	public function call(Request $req, Response $res, $names)
	{
		array_unshift($this->match, $req, $res);
		$this->names = $names;
		if (!isset($this->names["namespace"])) {
			return $this->next($this->cb, $this->match);
		}
		// require $this->names["namespace"]["autoload"] . ".php";
		// \App\AppAutoload::register();
		$middleware_is_defined = false;

		if (is_array($this->cb)) {
			if (count($this->cb) == 1) {
				if (isset($this->cb["middleware"])) {
					$middleware_is_defined = true;
				} else if (is_callable($this->cb[0])) {
					$cb = $this->cb[0];
				} else if (is_string($this->cb[0])) {
					$cb = $this->loadController($this->cb[0]);
				}
			} else {
				if (count($this->cb) == 2) {
					if (isset($this->cb["middleware"])) {
						$middleware_is_defined = true;
						$cb = array_pop($this->cb);
					} else {
						$this->cb["middleware"] = $this->cb[0];
						array_shift($this->cb);
						if (is_callable($this->cb)) {
							$cb = $this->cb;
						} else {
							$cb = $this->loadlController($this->cb);
						}
					}
				} else {
					$this->next($this->cb, $this->match);
				}
			}
		} else {
			if (is_callable($this->cb)) {
				$cb = $this->cb;
			} else {
				if (is_string($this->cb)) {
					$cb = $this->loadController($this->cb);
				}
			}
		}

		if ($middleware_is_defined) {

			if (!in_array($this->cb["middleware"], $this->names["middleware"])) {
				throw new RouterException($this->cb["middleware"] . " n'est pas un middleware definir.");
			}
			$middleware = $this->names["namespace"]["middleware"] . "\\" . ucfirst($this->cb["middleware"]);
			if (class_exists($middleware)) {
				$instance = new $middleware();
				$handler = [$instance, "handler"];
			} else {
				$handler = $this->cb["middleware"];
			}
			$status = call_user_func_array($handler, $this->match);
			if ($status == false) {
				die();
			}
		}

		if (isset($cb)) {
			return call_user_func_array($cb, $this->match);
		}

	}

	/**
	 * Next, lance successivement une liste de fonction.
	 *
	 * @param array $arr
	 * @param array $arg
	 * @return mixed|void
	 */
	private function next($arr, $arg)
	{
		if (is_callable($arr)) {
			return call_user_func_array($arr, $arg);
		}

		if (is_array($arr)) {
			array_reduce($arr, function($next, $cb) {
				if (is_null($next)) {
					if (is_string($cb)) {
						$cb = $this->loadController($cb);
					}
					return call_user_func_array($cb, $arg);
				} else {
					if ($next == true) {
						if (is_string($cb)) {
							$cb = $this->loadController($cb);
						}
						return call_user_func_array($cb, $arg);
					} else {
						die();
					}
				}
				return $next;
			});
		}
	}

	/**
	 * Charge les controllers
	 * 
	 * @param string $cbName
	 * @return mixed
	 */
	public function loadController($cbName)
	{
		list($class, $method) = explode(".", $cbName);
		$class = $this->names["namespace"]["controller"] . "\\" . ucfirst($class);
		return [new $class(), $method];
	}

}
