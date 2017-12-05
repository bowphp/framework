<?php

namespace Bow\Router\Route;

class Collection
{
    /**
     * @var array
     */
    private $collection;

    /**
     * @var array
     */
    private $error_code;

    /**
     * @var string
     */
    private $method;

    /**
     * RouterCollection constructor.
     *
     * @param $method
     */
    public function __construct($method)
    {
        $this->method = $method;
    }

    /**
     * Add new route
     *
     * @param Route $route
     */
    public function push(Route $route)
    {
        $this->collection[] = $route;
    }

    /**
     * Retourne la listes des routes de l'application
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->collection;
    }

    /**
     * mount, ajoute un branchement.
     *
     * @param  string   $branch
     * @param  callable $cb
     * @throws \Bow\Router\Exception\RouterException
     * @return Route
     */
    public function group($branch, callable $cb)
    {
        $branch = rtrim($branch, '/');

        if (!preg_match('@^/@', $branch)) {
            $branch = '/' . $branch;
        }

        if ($this->branch !== null) {
            $this->branch .= $branch;
        } else {
            $this->branch = $branch;
        }

        call_user_func_array($cb, [$this]);

        $this->branch = '';
        $this->globale_middleware = [];

        return $this;
    }

    /**
     * Lanceur de l'application
     *
     * @return mixed
     * @throws \Bow\Router\Exception\RouterException
     */
    public function run()
    {
        // Vérification de l'existance de methode de la requete dans
        // la collection de route
        if (!isset($this->collection[$this->method])) {
            // Vérification et appel de la fonction du branchement 404
            $this->response->statusCode(404);

            if (empty($this->error_code)) {
                $this->response->send('Cannot ' . $method . ' ' . $this->request->uri() . ' 404');
            }

            return false;
        }

        foreach ($this->collection[$this->method] as $key => $route) {
            // route doit être une instance de Route
            if (!($route instanceof Route)) {
                continue;
            }

            // Récupération du contenu des critères défini
            if (isset($this->with[$method][$route->getPath()])) {
                $with = $this->with[$method][$route->getPath()];
            } else {
                $with = [];
            }

            // Lancement de la recherche de la methode qui arrivée dans la requête
            // ensuite lancement de la vérification de l'url de la requête
            if (!$route->match($this->request->uri(), $with)) {
                $error = true;
                continue;
            }

            $this->current['path'] = $route->getPath();

            // Appel de l'action associer à la route
            $response = $route->call($this->request, $this->config['app']['classes']);

            if (is_string($response)) {
                $this->response->send($response);
            } elseif (is_array($response) || is_object($response)) {
                $this->response->json($response);
            }

            $error = false;
            break;
        }

        // Gestion de erreur
        if (!$error) {
            return true;
        }

        $this->response->statusCode(404);

        if (in_array(404, array_keys($this->error_code))) {
            $this->response->statusCode(404);
            $r = call_user_func($this->error_code[404]);
            return $this->response->send($r, true);
        }

        if ($this->config['view.404'] != false) {
            return $this->response->send(
                $this->response->view($this->config['view.404'])
            );
        }

        throw new RouterException('La route "'.$this->request->uri().'" n\'existe pas', E_ERROR);
    }
}
