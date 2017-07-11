<?php
namespace Bow\Application;

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
     * Contructeur
     *
     * @param string $path
     * @param callable $cb
     */
    public function __construct($path, $cb)
    {
        $this->cb = $cb;
        $this->path = str_replace('.', '\.', $path);
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
     * Retourne l'action a executé sur la route currente
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->cb;
    }

    /**
     * @param array|string $firewall
     */
    public function firewall($firewall)
    {
        if (!is_array($firewall)) {
            $firewall = [$firewall];
        }

        if (is_array($this->cb)) {
            if (! isset($this->cb['firewall'])) {
                $this->cb['firewall'] = $firewall;
            } else {
                $this->cb['firewall'] = array_merge(
                    $firewall,
                    is_array($this->cb['firewall']) ? $this->cb['firewall'] : [$this->cb['firewall']]
                );
            }
        } else {
            $this->cb = [
                'uses' => $this->cb,
                'firewall' => $firewall
            ];
        }
    }

    /**
     * match, vérifie si le url de la REQUEST est conforme à celle définir par le routeur
     *
     * @param string $uri L'url de la requête
     * @param array $with Les informations de restriction.
     * @return bool
     */
    public function match($uri, array $with = [])
    {
        $this->with = $with;

        // Normalisation de l'url du nagivateur.
        if (preg_match('~(.*)/$~', $uri, $match)) {
            $uri = end($match);
        }

        // Normalisation du path définir par le programmeur.
        if (preg_match('~(.*)/$~', $this->path, $match)) {
            $this->path = end($match);
        }

        // On retourne directement tout
        // pour gagner en performance.
        if ($this->path === $uri) {
            return true;
        }

        // On vérifie la longeur du path définie par le programmeur
        // avec celle de l'url courant dans le navigateur de l'utilisateur.
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

        // Copie de l'url courant pour éviter de la détruie
        $path = $uri;

        // Dans le case ou le dévéloppeur n'a pas ajouté de contrainte sur
        // les variables capturées
        if (empty($this->with)) {
            $path = preg_replace('~:\w+(\?)?~', '([^\s]+)$1', $this->path);
            preg_match_all('~:([a-z-0-9_-]+?)\?~', $this->path, $this->keys);
            $this->keys = end($this->keys);
            return $this->checkUrl($path, $uri);
        }

        // Dans le cas ou le dévéloppeur a ajouté de contrainte sur les variables
        // capturées
        if (!preg_match_all('~:([\w]+)?~', $this->path, $match)) {
            return $this->checkUrl($path, $uri);
        }

        $tmpPath =  $this->path;
        $this->keys = end($match);

        // Assication des critrères personnalisé.
        foreach ($this->keys as $key => $value) {
            if (array_key_exists($value, $this->with)) {
                $tmpPath = preg_replace('~:' . $value . '~', '(' . $this->with[$value] . ')', $tmpPath);
            }
        }

        // On rend vide le table d'association de critère personnalisé.
        $this->with = [];

        // Dans le case ou le path différent on récupère, on récupère celle dans $tmpPath
        if ($tmpPath !== $this->path) {
            $path = $tmpPath;
        }

        // Vérifcation de url et path PARSER
        return $this->checkUrl($path, $uri);
    }

    /**
     * @param $path
     * @param $uri
     * @return bool
     */
    private function checkUrl($path, $uri)
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
     * Fonction permettant de lancer les fonctions de rappel.
     *
     * @param Request $request
     * @param array $namespaces
     *
     * @return mixed
     */
    public function call(Request $request, array $namespaces)
    {
        // Association des parmatres à la request
        foreach ($this->keys as $key => $value) {
            if (!isset($this->match[$key])) {
                continue;
            }
            if (!is_int($this->match[$key])) {
                $this->params[$value] = $this->match[$key];
                continue;
            }

            $tmp = (int) $this->match[$key];
            $this->params[$value] = $tmp;
            $this->match[$key] = $tmp;
        }

        // Ajout des paramètres capturer à la requete
        $request->_setUrlParameters($this->params);

        return Actionner::call($this->cb, $this->match, $namespaces);
    }

    /**
     * Permet de donner un nom à la route
     *
     * @param string $name
     */
    public function name($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParamters()
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getParamter($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}