<?php
namespace Bow\Core;

use Bow\Support\Util;
use Bow\Http\Request;

/**
 * Bow Router
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */
Class Route
{
	/**
	 * Le callaback a lance si le url de la requête à matché.
	 *
	 * @var callable
	 */
	private $cb;

	/**
	 * Le chemin sur la route définir par l'utilisateur
	 *
	 * @var string
	 */
	private $path;

	/**
	 * key
	 *
	 * @var array
	 */
	private $keys = [];

	/**
	 * Liste de paramaters qui on matcher
	 *
	 * @var array
	 */
	private $match = [];

	/**
	 * Régle supplementaire de validation d'url
	 *
	 * @var array
	 */
	private $with = [];

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
	 * Retourne le chemin de la route currente
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * match, vérifie si le url de la REQUEST est conforme à celle définir par le routeur
	 *
	 * @param string $uri L'url de la requête
	 * @param array $with Les informations de restriction.
	 * @return bool
	 */
	public function match($uri, $with)
	{
		$this->with = $with;

		// Normalisation de l'url du nagivateur.
		if (preg_match("~(.+)/$~", $uri, $match)) {
			$uri = end($match);
		}

		// Normalisation du path définir par le programmeur.
		if (preg_match("~(.+)/$~", $this->path, $match)) {
			$this->path = end($match);
		}

		// On vérifie la longeur du path définie par le programmeur
		// avec celle de l'url courant dans le navigateur de l'utilisateur.
		// Pour éviter d'aller plus loin.
		if (count(explode("/", $this->path)) != count(explode("/", $uri))) {
			return false;
		}

		// Copie de l'url courant pour éviter de la détruie
		$path = $uri;

		// Dans le case ou le dévéloppeur n'a pas ajouté de contrainte sur
		// les variables capturées
		if (empty($this->with)) {
			$path = preg_replace("~:\w+~", "([^\s]+)", $this->path);
			preg_match_all("~:([\w]+)~", $this->path, $this->keys);
			array_shift($this->keys);
			$this->keys = $this->keys[0];
		} else {
			// Dans le cas ou le dévéloppeur a ajouté de contrainte sur les variables
			// capturées
			if (preg_match_all("~:([\w]+)~", $this->path, $match)) {
				$tmpPath =  $this->path;
				$this->keys = $match[1];

				foreach ($match[1] as $key => $value) {
					if (array_key_exists($value, $this->with)) {
						$tmpPath = preg_replace("~:$value~", "(" . $this->with[$value] . ")", $tmpPath);
					}
				}

				// Dans le case ou le path différent on récupère, on récupère celle dans $tmpPath
				if ($tmpPath !== $this->path) {
					$path = $tmpPath;
				}
			}

			$this->with = [];
		}

		// Vérifcation de url et path PARSER
		if (preg_match("~^$path$~", $uri, $match)) {
			array_shift($match);
			$this->match = str_replace("/", "", $match);
			return true;
		}

		return false;
	}

	/**
	 * Fonction permettant de lancer les fonctions de rappel.
	 *
	 * @param Request 	  $req
	 * @param array 	  $names
	 * @param Application $app
	 *
	 * @return mixed
	 */
	public function call(Request $req, $names, Application $app = null)
	{
		$params = [];

		foreach ($this->keys as $key => $value) {
			if (!is_int($this->match[$key])) {
				$params[$value] = $this->match[$key];
			} else {
				$tmp = (int) $this->match[$key];
				$params[$value] = $tmp;
				$this->match[$key] = $tmp;
			}
		}

		if ($app !== null) {
			array_unshift($this->match, $app);
		}

		$req::$params = (object) $params;

		return Util::launchCallback($this->cb, $this->match, $names);
	}
}