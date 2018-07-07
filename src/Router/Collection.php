<?php

namespace Bow\Router;

use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Support\Capsule;

class Collection
{
    /**
     * @var array
     */
    private static $collection;

    /**
     * @var array
     */
    private $error_code;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $in;

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
     * Get the Http method collection name
     *
     * @param string $method
     * @return Collection
     */
    public function in($method)
    {
        $this->in = $method;

        return $this;
    }

    /**
     * Add new route
     *
     * @param Route $route
     */
    public function push(Route $route)
    {
        static::$collection[$this->in] = $route;
    }

    /**
     * Retourne la listes des routes de l'application
     *
     * @param string|null $method
     * @return array
     */
    public function getRoutes($method = null)
    {
        if (!is_null($method) && isset(static::$collection[$method])) {
            return static::$collection[$method];
        }

        return static::$collection;
    }

    /**
     * Lanceur de l'application
     *
     * @param string $uri
     * @return mixed
     */
    public function run($uri)
    {
        $error = false;

        foreach ($this->getRoutes($this->method) as $key => $route) {
            // route doit être une instance de Route
            if (!($route instanceof Route)) {
                continue;
            }

            // Lancement de la recherche de la methode qui arrivée dans la requête
            // ensuite lancement de la vérification de l'url de la requête
            if (!$route->match($uri)) {
                continue;
            }

            // Appel de l'action associer à la route
            $response = $route->call(Capsule::getInstance()->make(Request::class));

            if (is_string($response)) {
                Capsule::getInstance()->make(Response::class)->send($response);
            } elseif (is_array($response) || is_object($response)) {
                Capsule::getInstance()->make(Response::class)->json($response);
            }

            $error = false;

            break;
        }

        return $error;
    }
}
