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
	 * Le callaback a lance si le url de la requete de matcher.
	 * 
	 * @var callable
	 */
	private $cb;
	/**
	 * Le chemin sur la route definir par l'utilisateur
	 *
	 * @var string
	 */
	private $path;
	/**
	 * key
	 *
	 * @var array
	 */
	private $key;
	/**
	 * Liste de paramaters qui on matcher
	 *
	 * @var array
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
			preg_match_all("~:([\w]+)~", $this->path, $this->key);
			array_shift($this->key);
			$this->key = $this->key[0];
		} else {
			if (preg_match_all("~:([\w]+)~", $this->path, $match)) {
                $tmpPath =  $this->path;
                $this->key = $match[1];
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
		
		$params = [];
		var_dump($this->key);
		foreach ($this->key as $key => $value) {
			$params[$value] = $this->match[$key];
		}

		$req->params = (object) $params;

		array_unshift($this->match, $req, $res);
		$this->names = $names;

		if (!isset($this->names["namespace"])) {
			return $this->next($this->cb, $this->match);
		}

		require $this->names["namespace"]["autoload"] . ".php";
		$autoload = $this->names["app_autoload"];
		$autoload::register();
		
		$middleware_is_defined = false;

		// Es-ce un tableau
		if (is_array($this->cb)) {

			// On determine le nombre d'element du tableau.
			if (count($this->cb) == 1) {

				// La cle middleware est t-elle définir
				if (isset($this->cb["middleware"])) {

					// On active Le mode de chargement de middleware.
					$middleware_is_defined = true;

					// Es-ce un callback
				} else if (is_callable($this->cb[0])) {

					// On récupére le callback dans la une variable.
					$cb = $this->cb[0];

					// Es-ce une chaine de caractere donc c'est un controlleur.
				} else if (is_string($this->cb[0])) {

					// On lance donc le chargeur de controlleur
					$cb = $this->loadController($this->cb[0]);
				}

				// Sinon on faire autre chose.
			} else {

				// La taille est égale a 2
				if (count($this->cb) == 2) {

					// La cle middleware est t-elle définir
					if (isset($this->cb["middleware"])) {

						// On active Le mode de chargement de middleware.
						$middleware_is_defined = true;

						// On récupére le dernier element
						$cb = array_pop($this->cb);

						// Sinon On faire autre chose.
					} else {

						// TODO: En Reflection 
						$this->cb["middleware"] = $this->cb[0];
						array_shift($this->cb);

						if (is_callable($this->cb)) {
							$cb = $this->cb;
						} else {
							$cb = $this->loadController($this->cb);
						}
					}

					// Sinon
				} else {
					$this->next($this->cb, $this->match);
				}

			}

			// Sinon
		} else {

			// Es-ce une closure
			if (is_callable($this->cb)) {

				// On récupére la closure
				$cb = $this->cb;

				// Sinon
			} else {

				// Es-ce une chaine de caractére
				if (is_string($this->cb)) {

					// On charge le controlleur.
					$cb = $this->loadController($this->cb);
				}
			}
		}

		if ($middleware_is_defined) {

			// On vérifie si le middleware est définie dans la configuration 
			if (!in_array($this->cb["middleware"], $this->names["middleware"])) {
				throw new RouterException($this->cb["middleware"] . " n'est pas un middleware definir.");
			}

			// Chargement du middleware
			$middleware = $this->names["namespace"]["middleware"] . "\\" . ucfirst($this->cb["middleware"]);

			// On vérifie si le middleware définie est une middleware valide.
			if (class_exists($middleware)) {

				$instance = new $middleware();

				// creation de la logic de lancement.
				$handler = [$instance, "handler"];
			} else {

				// c'est un middleware définir en closure.
				$handler = $this->cb["middleware"];
			}

			// Lancement du middleware.
			$status = call_user_func_array($handler, $this->match);

			// On arrêt tout en case de status false.
			if ($status == false) {
				die();
			}
		}

		// Verification de l'existance d'une fonction a appélée.
		if (isset($cb)) {
			return call_user_func_array($cb, $this->match);
		}

		return null;
	
	}

	/**
	 * Next, lance successivement une liste de fonction.
	 *
	 * @param array|callable $arr
	 * @param array|callable $arg
	 * @return mixed|void
	 */
	private function next($arr, $arg)
	{
		// Es-ce une closure.
		if (is_callable($arr)) {
			return call_user_func_array($arr, $arg);
		}

		// Es-ce un tableau
		if (is_array($arr)) {

			// Lancement de la procedure de lancement recursive.
			array_reduce($arr, function($next, $cb) use ($arg) {

				// $next est-il null
				if (is_null($next)) {

					// On lance la loader de controller si $cb est un String
					if (is_string($cb)) {
						$cb = $this->loadController($cb);
					}
					
					return call_user_func_array($cb, $arg);
				
				} else {

					// $next est-il a true.
					if ($next == true) {
						
						// On lance la loader de controller si $cb est un String
						if (is_string($cb)) {
							$cb = $this->loadController($cb);
						}
					
						return call_user_func_array($cb, $arg);
					
					} else {

						// Kill
						die();
					}
				}

				return $next;
			});

			// Sinon
		} else {

			// On lance la loader de controller si $cb est un String
			$cb = $this->loadController($arr);

			return call_user_func_array($cb, $arg);
		
		}
	}

	/**
	 * Charge les controllers
	 * 
	 * @param string $controllerName. Utilisant la dot notation
	 * @return array
	 */
	public function loadController($controllerName)
	{
		// Récupération de la classe et de la methode à lancer.
		list($class, $method) = explode(".", $controllerName);

		$class = $this->names["namespace"]["controller"] . "\\" . ucfirst($class);

		return [new $class(), $method];
	}

}
