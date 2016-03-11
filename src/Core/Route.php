<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */

namespace Bow\Core;

use Bow\Support\Util;
use Bow\Http\Request;

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
	private $key = [];

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
	 * match, vérifie si le path de la REQUEST est conforme à celle définir par le routeur
	 * 
	 * @param string $url
	 * @param array $with
     * @return bool
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
		
		$path = $url;

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

                // En case la path différent on récupère, on récupère celle dans $tmpPath
                if ($tmpPath !== $this->path) {
                    $path = $tmpPath;
                }
			}

			$this->with = [];
		}

		// Vérifcation de url
		if (preg_match("~^$path$~", $url, $match)) {
			array_shift($match);
			$this->match = str_replace("/", "", $match);
			return true;
		}

		return false;
	}

	/**
	 * Fonction permettant de lancer les fonctions de rappel.
	 * 
	 * @param Request $req
	 * @param array $names
	 * @return mixed
	 */
	public function call(Request $req, $names)
	{
		$params = [];

		foreach ($this->key as $key => $value) {
			if (!is_int($this->match[$key])) {
				$params[$value] = $this->match[$key];
			} else {
				$tmp = (int) $this->match[$key];
				$params[$value] = $tmp;
				$this->match[$key] = $tmp;
			}
		}

		$req->params = (object) $params;

		return Util::launchCallback($this->cb, $this->match, $names);
	}
}