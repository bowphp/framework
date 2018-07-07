<?php

namespace Bow\Application;

use Bow\Http\Redirect;
use Bow\Http\Request;
use Bow\Router\Route;
use Bow\Config\Config;
use Bow\Http\Response;
use Bow\Support\Capsule;
use Bow\Http\Exception\HttpException;
use Bow\Application\Resource\ResourceMethod;
use Bow\Application\Exception\RouterException;
use Bow\Application\Exception\ApplicationException;

class Application
{
    /**
     * @var string
     */
    private $version = '2.5.1';

    /**
     * @var Capsule
     */
    private $capsule;

    /**
     * @var bool
     */
    private $booted = false;

    /**
     * @var array
     */
    private $error_code = [];

    /**
     * @var array
     */
    private $globale_middleware = [];

    /**
     * Branchement global sur un liste de route
     *
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $special_method;

    /**
     * Method Http courrante.
     *
     * @var array
     */
    private $current = [];

    /**
     * Patter Singleton
     *
     * @var Application
     */
    private static $instance;

    /**
     * Collecteur de route.
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $local = [];

    /**
     * @var bool
     */
    private $disable_x_powered_by = false;

    /**
     * Private construction
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    private function __construct(Request $request, Response $response)
    {
        $this->request = $request;

        $this->response = $response;

        $this->capsule = Capsule::getInstance();
    }

    /**
     * Association de la configuration
     *
     * @param Config $config
     * @return void
     */
    public function bind(Config $config)
    {
        $this->config = $config;

        $this->boot();
    }

    /**
     * Démarrage de l'application
     *
     * @return void
     */
    private function boot()
    {
        if ($this->booted) {
            return;
        }

        if (method_exists($this->config, 'services')) {
            $services = $this->config->services();

            $service_collection = [];

            // Configuration des services
            foreach ($services as $service) {
                if (class_exists($service, true)) {
                    $class = new $service($this);

                    $class->make($this->config);

                    $service_collection[] = $class;
                }
            }

            // Démarage des services ou code d'initial
            foreach ($service_collection as $service) {
                $service->start();
            }
        }

        if (method_exists($this->config, 'boot')) {
            $this->config->boot();
        }

        $this->booted = true;
    }

    /**
     * Construction de l'application
     *
     * @param Request $request
     * @param Response $response
     * @return Application
     */
    public static function make(Request $request, Response $response)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($request, $response);
        }

        return static::$instance;
    }

    /**
     * Ajout un préfixe sur les routes
     *
     * @param string $branch
     * @param callable|string|array $cb
     * @return Application
     * @throws
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
     * Permet d'associer un middleware global sur une url
     *
     * @param array $middleware
     * @param callable|string|array $cb
     * @return Application
     */
    public function middleware($middleware = [], callable $cb = null)
    {
        $middleware = (array) $middleware;

        $this->globale_middleware = $middleware;

        if (is_callable($cb)) {
            $cb($this);

            $this->globale_middleware = [];
        }

        return $this;
    }

    /**
     * Ajout une route de type GET
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function get($path, $cb)
    {
        return $this->routeLoader('GET', $path, $cb);
    }

    /**
     * Ajout une route de type POST
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function post($path, $cb)
    {
        $input = $this->request->input();

        if (!$input->has('_method')) {
            return $this->routeLoader('POST', $path, $cb);
        }

        $method = strtoupper($input->get('_method'));

        if (in_array($method, ['DELETE', 'PUT'])) {
            $this->special_method = $method;
        }

        return $this->pushHttpVerbe($method, $path, $cb);
    }

    /**
     * Ajout une route de tout type
     *
     * GET, POST, DELETE, PUT, OPTIONS, PATCH
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Application
     * @throws
     */
    public function any($path, $cb)
    {
        foreach (['options', 'patch', 'post', 'delete', 'put', 'get'] as $method) {
            $this->$method($path, $cb);
        }

        return $this;
    }

    /**
     * Ajout une route de type DELETE
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function delete($path, $cb)
    {
        return $this->pushHttpVerbe('DELETE', $path, $cb);
    }

    /**
     * Ajout une route de type PUT
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function put($path, $cb)
    {
        return $this->pushHttpVerbe('PUT', $path, $cb);
    }

    /**
     * Ajout une route de type PATCH
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function patch($path, $cb)
    {
        return $this->pushHttpVerbe('PATCH', $path, $cb);
    }

    /**
     * Ajout une route de type PATCH
     *
     * @param string $path
     * @param callable $cb
     * @return Route
     */
    public function options($path, callable $cb)
    {
        return $this->pushHttpVerbe('OPTIONS', $path, $cb);
    }

    /**
     * Lance une fonction de rappel pour chaque code d'erreur HTTP
     *
     * @param int $code
     * @param callable $cb
     * @return Application
     */
    public function code($code, callable $cb)
    {
        $this->error_code[$code] = $cb;

        return $this;
    }

    /**
     * Match route de tout type de method
     *
     * @param array $methods
     * @param string $path
     * @param callable|string|array $cb
     * @return Application
     */
    public function match(array $methods, $path, $cb)
    {
        foreach ($methods as $method) {
            if ($this->request->method() === strtoupper($method)) {
                $this->routeLoader(strtoupper($method), $path, $cb);
            }
        }

        return $this;
    }

    /**
     * Permet d'ajouter les autres verbes http [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string $method
     * @param string $path
     * @param callable|array|string $cb
     * @return Route
     */
    private function pushHttpVerbe($method, $path, $cb)
    {
        $input = $this->request->input();

        if ($input->has('_method')) {
            if ($input->get('_method') === $method) {
                $method = $input->get('_method');
            }
        }

        return $this->routeLoader($method, $path, $cb);
    }

    /**
     * Lance le chargement d'une route.
     *
     * @param string $method
     * @param string $path
     * @param Callable|string|array $cb
     * @return Route
     */
    private function routeLoader($method, $path, $cb)
    {
        // construction du path original en fonction de la Config de l'application
        $path = $this->config['app.root'].$this->branch.$path;

        // route courante
        // methode courante
        $this->current = ['path' => $path, 'method' => $method];

        // Ajout de la nouvelle route
        $route = new Route($path, $cb);

        $route->middleware($this->globale_middleware);

        $this->routes[$method][] = $route;

        return $route;
    }

    /**
     * Lanceur de l'application
     *
     * @return mixed
     * @throws RouterException
     */
    public function send()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        if (env('MODE') == 'down') {
            abort(503);

            return true;
        }

        // Ajout de l'entête X-Powered-By
        if (!$this->disable_x_powered_by) {
            $this->response->addHeader('X-Powered-By', 'Bow Framework');
        }

        $this->branch = '';

        $method = $this->request->method();

        // vérification de l'existance d'une methode spécial
        // de type DELETE, PUT
        if ($method == 'POST') {
            if ($this->special_method !== null) {
                $method = $this->special_method;
            }
        }

        // Vérification de l'existance de methode de la requete dans
        // la collection de route
        if (!isset($this->routes[$method])) {
            // Vérification et appel de la fonction du branchement 404
            $this->response->statusCode(404);

            if (empty($this->error_code)) {
                $this->response->send(
                    sprintf('Cannot %s %s 404', $method, $this->request->uri())
                );
            }

            return false;
        }

        $response = null;

        foreach ($this->routes[$method] as $key => $route) {
            // route doit être une instance de Route
            if (!($route instanceof Route)) {
                continue;
            }

            // Lancement de la recherche de la methode qui arrivée dans la requête
            // ensuite lancement de la vérification de l'url de la requête
            if (!$route->match($this->request->uri())) {
                continue;
            }

            $this->current['path'] = $route->getPath();

            // Appel de l'action associer à la route
            $response = $route->call($this->request);

            break;
        }

        // Gestion de erreur
        if (!is_null($response)) {
            return $this->sendResponse($response);
        }

        // Application du code d'erreur 404
        $this->response->statusCode(404);

        if (in_array(404, array_keys($this->error_code))) {
            $this->response->statusCode(404);

            $r = call_user_func($this->error_code[404]);

            return $this->response->send($r, true);
        }

        if (in_array($code = http_response_code(), array_keys($this->error_code))) {
            $this->response->statusCode($code);

            $r = call_user_func($this->error_code[$code]);

            return $this->response->send($r, true);
        }

        if (is_string($this->config['view.404'])) {
            return $this->response->send(
                $this->response->view($this->config['view.404'])
            );
        }

        throw new RouterException(
            sprintf('La route "%s" n\'existe pas', $this->request->uri()),
            E_ERROR
        );
    }

    /**
     * Envoi la reponse au client
     *
     * @param $response
     * @return null
     */
    public function sendResponse($response)
    {
        if (is_string($response)) {
            $this->response->send($response);
        }

        if (is_array($response) || is_object($response)) {
            $this->response->json($response);
        }

        if ($response instanceof Redirect) {
            return $this->response->addHeader('Location', (string) $response);
        }

        return;
    }

    /**
     * Permet d'active l'écriture le l'entête X-Powered-By
     * dans la réponse de la réquête.
     *
     * @return void
     */
    public function disableXpoweredBy()
    {
        $this->disable_x_powered_by = true;
    }

    /**
     * REST API Maker.
     *
     * @param string $url
     * @param string|array $controller_name
     * @param array $where
     * @return Application
     *
     * @throws ApplicationException
     */
    public function resources($url, $controller_name, array $where = [])
    {
        if (!is_string($controller_name) && !is_array($controller_name)) {
            throw new ApplicationException('Le premier paramètre doit être un array ou une chaine de caractère', 1);
        }

        $controller = '';

        $internal_middleware = null;

        $ignore_method = [];

        $controller_name = (array) $controller_name;

        if (isset($controller_name['middleware'])) {
            $internal_middleware = $controller_name['middleware'];

            unset($controller_name['middleware']);

            $next = $this->capsule(Actionner::class)->call([
                'middleware' => $internal_middleware
            ], $this->request);

            if ($next === false) {
                return $this;
            }
        }

        if (isset($controller_name['uses'])) {
            $controller = $controller_name['uses'];

            unset($controller_name['uses']);
        }

        if (isset($controller_name['ignores'])) {
            $ignore_method = $controller_name['ignores'];

            unset($controller_name['ignores']);
        }

        // normalize url
        $url = preg_replace('/\/+$/', '', $url);

        // Association de url prédéfinie
        foreach (ResourceMethod::take() as $key => $value) {
            // on vérifie si la methode de appelé est ignoré
            if (in_array($value['call'], $ignore_method)) {
                continue;
            }

            // Formate controlleur
            $bind_controller = $controller . '@' . $value['call'];

            $path = $url . $value['url'];

            // Lancement de la methode de mapping de route.
            $route = call_user_func_array(
                [$this, $value['method']],
                [rtrim($path, '/'), $bind_controller]
            );

            // Ajout de nom sur la route
            $route->name($value['call']);

            // Association des critères définies
            if (empty($where)) {
                continue;
            }

            $data = [];

            if (preg_match('/:id/', $path)) {
                if (isset($where['id'])) {
                    $data = $where;
                } else {
                    $data = ['id' => $where[0]];
                }
            }

            $route->where(array_merge($data, $where));
        }

        return $this;
    }

    /**
     * Retourne les définir pour une methode HTTP
     *
     * @param string $method
     * @return array
     */
    public function getRouteMethod($method)
    {
        return $this->routes[$method];
    }

    /**
     * __call fonction magic php
     *
     * @param string $method
     * @param array  $param
     * @return mixed
     *
     * @throws ApplicationException
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
     * Abort application
     *
     * @param $code
     * @param $message
     * @param array $headers
     * @return void
     *
     * @throws HttpException
     */
    public function abort($code = 500, $message = '', array $headers = [])
    {
        $this->response->statusCode($code);

        foreach ($headers as $key => $value) {
            $this->response->addHeader($key, $value);
        }

        if ($message == null) {
            $message = 'Le procéssus a été suspendu.';
        }

        throw new HttpException($message);
    }

    /**
     * Build dependance
     *
     * @param null $name
     * @param callable|null $callable
     * @return Capsule|mixed
     * @throws ApplicationException
     */
    public function capsule($name = null, callable $callable = null)
    {
        if (is_null($name)) {
            return $this->capsule;
        }

        if (is_null($callable)) {
            return $this->capsule->make($name);
        }

        if (!is_callable($callable)) {
            throw new ApplicationException('le deuxième paramètre doit être un callable.');
        }

        return $this->capsule->bind($name, $callable);
    }

    /**
     * __invoke
     *
     * @param array ...$params
     * @return Capsule
     * @throws ApplicationException
     */
    public function __invoke(...$params)
    {
        if (count($params)) {
            return $this->capsule;
        }

        if (count($params) > 2) {
            throw new ApplicationException('Deuxième paramètre doit être passer.');
        }

        if (count($params) == 1) {
            return $this->capsule($params[0]);
        }

        return $this->capsule($params[0], $params[1]);
    }
}
