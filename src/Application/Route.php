<?php

namespace Bow\Application;

use Bow\Http\Request;
use Bow\Config\Config;

class Route
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
     * @var string
     */
    private $name;

    /**
     * key
     *
     * @var array
     */
    private $keys = [];

    /**
     * @var array
     */
    private $params = [];

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
     * Application configuration
     *
     * @var Config
     */
    private $config;

    /**
     * Contructeur
     *
     * @param string   $path
     * @param callable $cb
     */
    public function __construct($path, $cb)
    {
        $this->config = Config::getInstance();
        $this->cb = $cb;
        $this->path = str_replace('.', '\.', $path);
        $this->match = [];
    }

    /**
     * Récupère le chemin de la route courante
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Récupère l'action a executé sur la route courante
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->cb;
    }

    /**
     * Ajout middleware
     *
     * @param  array|string $middleware
     * @return Route
     */
    public function middleware($middleware)
    {
        $middleware = (array) $middleware;

        if (! is_array($this->cb)) {
            $this->cb = [
                'uses' => $this->cb,
                'middleware' => $middleware
            ];

            return $this;
        }


        if (!isset($this->cb['middleware'])) {
            $this->cb['middleware'] = $middleware;
        } else {
            $this->cb['middleware'] = array_merge($middleware, (array) $this->cb['middleware']);
        }

        return $this;
    }

    /**
     * Permet de vérifier si l'url de la réquête est
     * conforme à celle définir par le routeur
     *
     * @param  string $uri
     * @return bool
     */
    public function match($uri)
    {
        // Normalisation de l'url du nagivateur.
        if (preg_match('~(.*)/$~', $uri, $match)) {
            $uri = end($match);
        }

        // Normalisation du path défini par le programmeur.
        if (preg_match('~(.*)/$~', $this->path, $match)) {
            $this->path = end($match);
        }

        // On retourne directement tout
        // pour gagner en performance.
        if ($this->path === $uri) {
            return true;
        }

        // On vérifie la longeur du path défini par le programmeur
        // avec celle de l'url courante dans le navigateur de l'utilisateur.
        // Pour éviter d'aller plus loin.
        $path = implode(
            '',
            preg_split(
                '/(\/:[a-z0-9-_]+\?)/',
                $this->path
            )
        );

        if (count(explode('/', $path)) != count(explode('/', $uri))) {
            if (count(explode('/', $this->path)) != count(explode('/', $uri))) {
                return false;
            }
        }

        // Copie de l'url
        $path = $uri;

        // Dans le case ou le dévéloppeur n'a pas ajouté
        // de contrainte sur les variables capturées
        if (empty($this->with)) {
            $path = preg_replace('~:\w+(\?)?~', '([^\s]+)$1', $this->path);
            preg_match_all('~:([a-z-0-9_-]+?)\?~', $this->path, $this->keys);
            $this->keys = end($this->keys);
            return $this->checkRequestUri($path, $uri);
        }

        // Dans le cas ou le dévéloppeur a ajouté des contraintes
        // sur les variables capturées
        if (!preg_match_all('~:([\w]+)?~', $this->path, $match)) {
            return $this->checkRequestUri($path, $uri);
        }

        $tmp_path = $this->path;
        $this->keys = end($match);

        // Association des critrères personnalisé.
        foreach ($this->keys as $key => $value) {
            if (array_key_exists($value, $this->with)) {
                $tmp_path = preg_replace('~:' . $value . '~', '(' . $this->with[$value] . ')', $tmp_path);
            }
        }

        // On rend vide le table d'association de critère personnalisé.
        $this->with = [];

        // Dans le case ou le path différent on récupère, on récupère celle dans $tmp_path
        if ($tmp_path !== $this->path) {
            $path = $tmp_path;
        }

        // Vérifcation de url et path PARSER
        return $this->checkRequestUri($path, $uri);
    }

    /**
     * Vérifie url de la réquête
     *
     * @param $path
     * @param $uri
     * @return bool
     */
    private function checkRequestUri($path, $uri)
    {
        if (strstr($path, '?') == '?') {
            $uri = rtrim($uri, '/').'/';
        }

        // Vérifcation de url et path PARSER
        $path = str_replace('~', '\\~', $path);

        if (preg_match('~^'. $path . '$~', $uri, $match)) {
            array_shift($match);
            $this->match = str_replace('/', '', $match);
            return true;
        }

        return false;
    }

    /**
     * Lance une personnalisation de route.
     *
     * @param array|string $where
     * @param string       $regex_constraint
     *
     * @return Application
     */
    public function where($where, $regex_constraint = null)
    {
        if (is_array($where)) {
            $other_rule = $where;
        } else {
            $other_rule = [$where => $regex_constraint];
        }

        $this->with = array_merge($this->with, $other_rule);

        return $this;
    }

    /**
     * Fonction permettant de lancer les fonctions de rappel.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function call(Request $request)
    {
        // Association des parmatres à la request
        foreach ($this->keys as $key => $value) {
            if (!isset($this->match[$key])) {
                continue;
            }

            if (!is_int($this->match[$key])) {
                $this->params[$value] = urldecode($this->match[$key]);
                continue;
            }

            $tmp = (int) $this->match[$key];
            $this->params[$value] = $tmp;
            $this->match[$key] = $tmp;
        }

        // Ajout des paramètres capturer à la requete
        $request->_setUrlParameters($this->params);

        return Actionner::getInstance()->call($this->cb, $this->match);
    }

    /**
     * Permet de donner un nom à la route
     *
     * @param string $name
     */
    public function name($name)
    {
        $this->name = $name;
        $routes = $this->config['app.routes'];
        $this->config['app.routes'] = array_merge($routes, [$name => $this->getPath()]);
    }

    /**
     * Récupère le nom de la route
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Récupère les paramètres
     *
     * @return array
     */
    public function getParamters()
    {
        return $this->params;
    }

    /**
     * Récupère un élément des paramètres
     *
     * @param string $key
     * @return string|null
     */
    public function getParamter($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}
