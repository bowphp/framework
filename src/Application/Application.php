<?php
namespace Bow\Application;

use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Logger\Logger;
use InvalidArgumentException;
use Bow\Exception\ApplicationException;

/**
 * Create and maintener by diagnostic developpers teams:
 *
 * @author Etchien Boa <geekroot9@gmail.com>
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */
class Application
{
    /**
     * @var string
     */
    private $version = '0.1.1';

    /**
     * @var array
     */
    private $errorCode = [];

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
    private $branch = '';

    /**
     * @var string
     */
    private $specialMethod = null;

    /**
     * Method Http courrante.
     *
     * @var string
     */
    private $currentMethod = '';

    /**
     * Enrégistre l'information la route courrante
     *
     * @var string
     */
    private $currentPath = '';

    /**
     * Patter Singleton
     *
     * @var Application
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
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Configuration|null
     */
    private $config = null;

    /**
     * @var array
     */
    private $local = [];

    /**
     * @var bool
     */
    private $disableXpoweredBy = false;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Private construction
     *
     * @param Configuration $config
     * @param Request $request
     * @param Response $response
     */
    private function __construct(Configuration $config, Request $request, Response $response)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;

        $logger = new Logger($config->getLoggerMode(), $config->getLoggerPath() . '/error.log');
        $logger->register();
        $this->logger = $logger;
    }

    /**
     * Private __clone
     */
    private function __clone(){}

    /**
     * Pattern Singleton.
     *
     * @param Configuration $config
     * @param Request $request
     * @param Response $response
     * @return Application
     */
    public static function make(Configuration $config, Request $request, Response $response)
    {
        if (static::$inst === null) {
            static::$inst = new static($config, $request, $response);
        }

        return static::$inst;
    }

    /**
     * mount, ajoute un branchement.
     *
     * @param string $branch
     * @param callable $cb
     * @throws ApplicationException
     * @return Application
     */
    public function group($branch, Callable $cb)
    {
        $branch = rtrim($branch, '/');
        if (!preg_match('@^/@', $branch)) {
            $branch = '/' . $branch;
        }
        $this->branch = $branch;
        call_user_func_array($cb, [$this->request]);
        $this->branch = '';
        return $this;
    }

    /**
     * get, route de type GET ou bien retourne les variable ajoutés dans Bow
     *
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name [optinal]   Le nom de la route ou la fonction à lancer.
     * @param callable|null 		$cb   [optinal]   La fonction à lancer
     * @return Application|string
     */
    public function get($path, $name = null, $cb = null)
    {
        if ($name === null) {
            $key = $path;

            if (in_array($key, $this->local)) {
                return $this->local[$key];
            }

            if (($method = $this->getConfigMethod($key, 'get')) !== false) {
                return $this->config->$method();
            }

            return null;
        }

        return $this->routeLoader('GET', $path, $name, $cb);
    }

    /**
     * post, route de type POST
     *
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb   [optional]  La fonction à lancer
     * @return Application
     */
    public function post($path, $name, Callable $cb = null)
    {
        $body = $this->request->body();

        if ($body->has('method')) {
            $this->specialMethod = $method = strtoupper($body->get('method'));
            if (in_array($method, ['DELETE', 'PUT'])) {
                $this->addHttpVerbe($method, $path, $name, $cb);
            }
            return $this;
        }

        return $this->routeLoader('POST', $path, $name, $cb);
    }

    /**
     * any, route de tout type GET|POST|DELETE|PUT
     *
     * @param string   $path La route à mapper
     * @param Callable $cb   La fonction à lancer
     * @return Application
     */
    public function any($path, Callable $cb)
    {
        foreach(['post', 'delete', 'put', 'get'] as $function) {
            $this->$function($path, $cb);
        }

        return $this;
    }

    /**
     * delete, route de tout type DELETE
     *
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb     La fonction à lancer
     * @return Application
     */
    public function delete($path, $name, Callable $cb = null)
    {
        return $this->addHttpVerbe('DELETE', $path, $name, $cb);
    }

    /**
     * put, route de tout type PUT
     *
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb     La fonction à lancer
     * @return Application
     */
    public function put($path, $name, Callable $cb = null)
    {
        return $this->addHttpVerbe('PUT', $path, $name, $cb);
    }

    /**
     * patch, route de tout type PATCH
     *
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb     La fonction à lancer
     * @return Application
     */
    public function patch($path, $name, Callable $cb = null)
    {
        return $this->addHttpVerbe('PATCH', $path, $name, $cb);
    }

    /**
     * code, Lance une fonction en fonction du code d'erreur HTTP
     *
     * @param int $code Le code d'erreur
     * @param callable $cb La fonction à lancer
     * @return Application
     */
    public function code($code, callable $cb)
    {
        $this->errorCode[$code] = $cb;
        return $this;
    }

    /**
     * match, route de tout type de method
     *
     * @param array $methods
     * @param string $path
     * @param callable $cb La fonction à lancer
     * @return Application
     */
    public function match(array $methods, $path, Callable $cb = null)
    {
        foreach($methods as $method) {
            if ($this->request->method() === strtoupper($method)) {
                $this->routeLoader(strtoupper($method), $path , $cb, null);
            }
        }

        return $this;
    }

    /**
     * addHttpVerbe, permet d'ajouter les autres verbes http
     * [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string  				$method La methode HTTP
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb     La fonction à lancer
     *
     * @return Application
     */
    private function addHttpVerbe($method, $path, $name, Callable $cb = null)
    {
        $body = $this->request->body();
        $flag = true;

        if ($body !== null) {
            if ($body->has('_method')) {
                if ($body->get('_method') === $method) {
                    $this->routeLoader($method, $path, $name, $cb);
                }
                $flag = false;
            }
        }

        if ($flag) {
            $this->routeLoader($method, $path, $name, $cb);
        }

        return $this;
    }

    /**
     * routeLoader, lance le chargement d'une route.
     *
     * @param string  				$method La methode HTTP
     * @param string 				$path   La route à mapper
     * @param Callable|string|array $name   Le nom de la route ou la fonction à lancer.
     * @param callable 				$cb     La fonction à lancer
     *
     * @return Application
     */
    private function routeLoader($method, $path, $name, Callable $cb = null)
    {

        if (!preg_match('@^/@', $path)) {
            $path = '/' . $path;
        }

        // construction du path original en fonction de la configuration de l'application
        $path = $this->config->getApproot() . $this->branch . $path;

        if (is_callable($name)) {
            $cb = $name;
            $name = null;
        }

        if (is_array($name)) {
            $cb = $name;
            $name = null;
            if (isset($cb['name'])) {
                $name = $cb['name'];
                unset($cb['name']);
            }
        }

        if (is_string($name)) {
            if (!preg_match('/^[a-z]+(\.|@)[a-z]+$/i', $name)) {
                $this->namedRoute($path, $name);
            } else {
                $cb = $name;
                $name = null;
            }
        }

        // Ajout d'un nouvelle route sur l'en definie.
        static::$routes[$method][] = new Route($path, $cb);

        // route courante
        $this->currentPath = $path;

        // methode courante
        $this->currentMethod = $method;

        return $this;
    }

    /**
     * Lance une personnalisation de route.
     *
     * @param string $var
     * @param string $regexContrainte
     *
     * @return Application
     */
    public function where($var, $regexContrainte = null)
    {
        if (is_array($var)) {
            $otherRule = $var;
        } else {
            $otherRule = [$var => $regexContrainte];
        }

        // Quand le tableau de collection des contraintes sur les variables est vide
        if (empty($this->with)) {
            // Si on crée un nouvelle entre dans le tableau avec le nom de la methode HTTP
            // courante dont la valeur est un tableau, ensuite dans ce tableau on crée une
            // autre entré avec comme clé le path définie par le developpeur et pour valeur
            // les contraintes sur les variables.
            $this->with[$this->currentMethod] = [];
            $this->with[$this->currentMethod][$this->currentPath] = $otherRule;
        } else {
            // Quand le tableau de collection des contraintes sur les variables n'est pas vide
            // On vérifie l'existance de clé portant le nom de la methode HTTP courant
            // si la elle existe alors on fusionne l'ancien contenu avec la nouvelle.
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
     *
     * @return mixed
     */
    public function run($cb = null)
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        // Ajout de l'entête X-Powered-By
        if (!$this->disableXpoweredBy) {
            $this->response->addHeader('X-Powered-By', 'Bow Framework');
        }

        // drapeaux d'erreur.
        $error = true;

        if (is_callable($cb)) {
            if (call_user_func_array($cb, [$this->request])) {
                die();
            }
        }

        $this->branch = '';
        $method = $this->request->method();

        // vérification de l'existance d'une methode spécial
        // de type DELETE, PUT
        if ($method == 'POST') {
            if ($this->specialMethod !== null) {
                $method = $this->specialMethod;
            }
        }

        // Vérification de l'existance de methode de la requete dans
        // la collection de route
        if (! isset(static::$routes[$method])) {
            // Vérification et appel de la fonction du branchement 404
            if (empty($this->errorCode)) {
                $this->response->send('Cannot ' . $method . ' ' . $this->request->uri() . ' 404');
            }
            $this->response->code(404);
            return false;
        }

        foreach (static::$routes[$method] as $key => $route) {
            // route doit être une instance de Route
            if (! ($route instanceof Route)) {
                continue;
            }

            // récupération du contenu de la where
            if (isset($this->with[$method][$route->getPath()])) {
                $with = $this->with[$method][$route->getPath()];
            } else {
                $with = [];
            }

            // Lancement de la recherche de la method qui arrivée dans la requete
            // ensuite lancement de la verification de l'url de la requete
            // execution de la fonction associé à la route.
            if ($route->match($this->request->uri(), $with)) {
                $this->currentPath = $route->getPath();
                // appel requête fonction
                $response = $route->call($this->request, $this->config->getNamespace(), $this);
                if (is_string($response)) {
                    $this->response->send($response);
                } else if (is_array($response) || is_object($response)) {
                    $this->response->json($response);
                }
            }
        }
        return true;
    }

    /**
     * Set, permet de rédéfinir quelque élément de la configuartion de
     * façon élégante.
     *
     * @param string $key
     * @param string $value
     *
     * @throws InvalidArgumentException
     *
     * @return Application|string
     */
    public function set($key, $value)
    {
        $method = $this->getConfigMethod($key, 'set');

        // Vérification de l
        if ($method) {
            if (method_exists($this->config, $method)) {
                return $this->config->$method($value);
            }
        } else {
            $this->local[$key] = $value;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $prefix
     *
     * @return string|bool
     */
    private function getConfigMethod($key, $prefix)
    {
        switch ($key) {
            case 'view':
                $method = 'Viewpath';
                break;
            case 'engine':
                $method = 'Engine';
                break;
            case 'root':
                $method = 'Approot';
                break;
            default:
                $method = false;
                break;
        }

        return is_string($method) ? $prefix . $method : $method;
    }

    /**
     * d'active l'ecriture le l'entête X-Powered-By
     */
    public function disableXPoweredBy()
    {
        $this->disableXpoweredBy = true;
    }

    /**
     * REST API Maker.
     *
     * @param string $url
     * @param string|array $controllerName
     * @param array $where
     * @return $this
     * @throws ApplicationException
     */
    public function resources($url, $controllerName, array $where = [])
    {
        if (!is_string($controllerName) && !is_array($controllerName)) {
            throw new ApplicationException('Le premier paramètre doit être un array ou une chaine de caractère', 1);
        }

        $controller = '';
        $internalMiddleware = null;
        $ignoreMethod = [];
        $valideMethod = [
            [
                'url'    => '/',
                'call'   => 'index',
                'method' => 'get'
            ],
            [
                'url'    => '/',
                'call'   => 'store',
                'method' => 'post'
            ],
            [
                'url'    => '/:id',
                'call'   => 'show',
                'method' => 'get'
            ],
            [
                'url'    => '/:id',
                'call'   => 'update',
                'method' => 'put'
            ],
            [
                'url'    => '/:id',
                'call'   => 'destroy',
                'method' => 'delete'
            ],
            [
                'url'    => '/:id/edit',
                'call'   => 'edit',
                'method' => 'get'
            ],
            [
                'url'    => '/create',
                'call'   => 'create',
                'method' => 'get'
            ]
        ];

        if (is_array($controllerName)) {
            if (isset($controllerName['middleware'])) {
                $internalMiddleware = $controllerName['middleware'];
                unset($controllerName['middleware']);
                $next = Actionner::call(['middleware' => $internalMiddleware], $this->request);
                if ($next === false) {
                    return $this;
                }
            }

            if (isset($controllerName['use'])) {
                $controller = $controllerName['use'];
                unset($controllerName['use']);
            }

            if (isset($controllerName['ignores'])) {
                $ignoreMethod = $controllerName['ignores'];
                unset($controllerName['ignores']);
            }
        } else  {
            $controller = $controllerName;
        }

        // normalize url
        $url = preg_replace('/\/+$/', '', $url);

        // Association de url prédéfinie
        foreach ($valideMethod as $key => $value) {
            // on vérifie si la methode de appelé est ignoré
            if (!in_array($value['call'], $ignoreMethod)) {

                // Formate controlleur
                $bindController = $controller . '@' . $value['call'];
                $path = $url . $value['url'];
                $this->namedRoute($path, strtolower(preg_replace('/controller/i', '',$controller)) . '.' . $value['call']);

                // Lancement de la methode de mapping de route.
                call_user_func_array([$this, $value['method']], [rtrim($path, '/'), $bindController]);

                // Association des critères définies
                if (!empty($where)) {
                    $data = [];
                    if (preg_match('/:id/', $path)) {
                        if (isset($where['id'])) {
                            $data = $where;
                        } else {
                            $data = ['id' => $where[0]];
                        }
                    }

                    $this->where(array_merge($data, $where));
                }
            }
        }

        return $this;
    }

    /**
     * Fonction retournant une instance de logger.
     *
     * @return Logger
     */
    public function log()
    {
        return $this->logger;
    }

    /**
     * Ajout une route nommé.
     *
     * @param string $uri  L'url pointant sur la route.
     * @param string $name Le nom de la routes
     */
    private function namedRoute($uri, $name)
    {
        $route[$name] = $uri;
        $routes = $this->config->getApplicationRoutes();
        $routes = array_merge($routes, $route);
        $this->config->setApplicationRoutes($routes);
    }

    /**
     * Retourne la listes des routes de l'application
     *
     * @return array Liste des routes définir dans l'application
     */
    public function getRoutes()
    {
        return static::$routes;
    }

    /**
     * Retourne les définir pour une methode HTTP
     *
     * @param string $method
     * @return Route
     */
    public function getMethodRoutes($method)
    {
        return static::$routes[$method];
    }

    /**
     * __call fonction magic php
     *
     * @param string $method
     * @param array $param
     *
     * @throws ApplicationException
     *
     * @return mixed
     */
    public function __call($method, array $param)
    {
        if (method_exists($this->config, $method)) {
            return call_user_func_array([$this->config, $method], $param);
        }

        if (in_array($method, $this->local)) {
            return call_user_func_array($this->local[$method], $param);
        }

        throw new ApplicationException('La methode ' . $method . ' n\'exist pas.', E_ERROR);
    }

    /**
     * Permet de récupérer la version de l'application
     *
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $code = http_response_code();
        if (in_array($code, array_keys($this->errorCode))) {
            $this->response->code($code);
            call_user_func($this->errorCode[$code]);
        }
    }
}