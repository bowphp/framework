<?php

namespace Bow\Application;

use Bow\Application\Exception\ApplicationException;
use Bow\Configuration\Loader;
use Bow\Contracts\ResponseInterface;
use Bow\Http\Exception\HttpException;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Router\Exception\RouterException;
use Bow\Router\Resource;
use Bow\Router\Route;
use Bow\Support\Capsule;

class Application
{
    /**
     * The Capsule instance
     *
     * @var Capsule
     */
    private $capsule;

    /**
     * The booting flag
     *
     * @var bool
     */
    private $booted = false;

    /**
     * Define the functions related to an http
     * code executed if this code is up
     *
     * @var array
     */
    private $error_code = [];

    /**
     * Define the gloal middleware
     *
     * @var array
     */
    private $middlewares = [];

    /**
     * The routing prefixer
     *
     * @var string
     */
    private $prefix;

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
     * The Application instance
     *
     * @var Application
     */
    private static $instance;

    /**
     * Route collection.
     *
     * @var array
     */
    private $routes = [];

    /**
     * The HTTP Request
     *
     * @var Request
     */
    private $request;

    /**
     * The HTTP Response
     *
     * @var Response
     */
    private $response;

    /**
     * The Configuration Loader instance
     *
     * @var Loader
     */
    private $config;

    /**
     * This define if the X-powered-By header must be put in response
     *
     * @var bool
     */
    private $disable_x_powered_by = false;

    /**
     * Application constructor
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

        $this->capsule->instance('request', $request);

        $this->capsule->instance('response', $response);

        $this->capsule->instance('app', $this);
    }

    /**
     * Get container
     *
     * @return Capsule
     */
    public function getContainer()
    {
        return $this->capsule;
    }

    /**
     * Configuration Association
     *
     * @param Loader $config
     * @return void
     */
    public function bind(Loader $config)
    {
        $this->config = $config;

        $this->capsule->instance('config', $config);

        $this->boot();
    }

    /**
     * Boot the application
     *
     * @return void
     */
    private function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->config->boot();

        $this->booted = true;
    }

    /**
     * Build the application
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
     * Add a prefix on the roads
     *
     * @param string $prefix
     * @param callable $cb
     *
     * @return Application
     *
     * @throws
     */
    public function prefix($prefix, callable $cb)
    {
        $prefix = rtrim($prefix, '/');

        if (!preg_match('@^/@', $prefix)) {
            $prefix = '/' . $prefix;
        }

        if ($this->prefix !== null) {
            $this->prefix .= $prefix;
        } else {
            $this->prefix = $prefix;
        }

        call_user_func_array($cb, [$this]);

        $this->prefix = '';

        return $this;
    }

    /**
     * Allows to associate a global middleware on an route
     *
     * @param array $middlewares
     * @return Application
     */
    public function middleware($middlewares)
    {
        $middlewares = (array) $middlewares;

        $this->middlewares = [];

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $this->middlewares[] = $middleware;
            } elseif (class_exists($middleware, true)) {
                $this->middlewares[] = [new $middleware, 'process'];
            } else {
                $this->middlewares[] = $middleware;
            }
        }

        return $this;
    }

    /**
     * Route mapper
     *
     * @param array $definition
     * @throws RouterException
     */
    public function route(array $definition)
    {
        if (!isset($definition['path'])) {
            throw new RouterException('The undefined path');
        }

        if (!isset($definition['method'])) {
            throw new RouterException('Unspecified method');
        }

        if (!isset($definition['handler'])) {
            throw new RouterException('Undefined controller');
        }

        $method = $definition['method'];

        $path = $definition['path'];

        $where = $definition['where'] ?? [];

        $cb = (array) $definition['handler'];

        if (isset($cb['middleware'])) {
            unset($cb['middleware']);
        }

        if (isset($cb['controller'])) {
            unset($cb['controller']);
        }

        $route = $this->pushHttpVerbe($method, $path, $cb);

        if (isset($definition['middleware'])) {
            $route->middleware($definition['middleware']);
        }

        $route->where($where);
    }

    /**
     * Add a route for
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
     * Add a GET route
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
     * Add a POST route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function post($path, $cb)
    {
        $input = $this->request;

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
     * Add a DELETE route
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
     * Add a PUT route
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
     * Add a PATCH route
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
     * Add a OPTIONS route
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
     * Launch a callback function for each HTTP error code.
     * When the define code match with response code.
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
                $this->pushHttpVerbe(strtoupper($method), $path, $cb);
            }
        }

        return $this;
    }

    /**
     * Add other HTTP verbs [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string $method
     * @param string $path
     * @param callable|array|string $cb
     * @return Route
     */
    private function pushHttpVerbe($method, $path, $cb)
    {
        $input = $this->request;

        if ($input->has('_method')) {
            if ($input->get('_method') === $method) {
                $method = $input->get('_method');
            }
        }

        return $this->routeLoader($method, $path, $cb);
    }

    /**
     * Start loading a route.
     *
     * @param string $method
     * @param string $path
     * @param Callable|string|array $cb
     * @return Route
     */
    private function routeLoader($method, $path, $cb)
    {
        // We build the original path based on the application loader
        $path = $this->config['app.root'].$this->prefix.$path;

        // We define the current route and current method
        $this->current = ['path' => $path, 'method' => $method];

        // We add the new route
        $route = new Route($path, $cb);

        $route->middleware($this->middlewares);

        $this->routes[$method][] = $route;

        $route->middleware('trim');

        if (in_array($method, ['POST', 'DELETE', 'PUT'])) {
            $route->middleware('csrf');
        }

        return $route;
    }

    /**
     * Launcher of the application
     *
     * @return mixed
     * @throws RouterException
     */
    public function send()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        // We add of the X-Powered-By header when disable_x_powered_by is true
        if (!$this->disable_x_powered_by) {
            $this->response->addHeader('X-Powered-By', 'Bow Framework');
        }

        $this->prefix = '';

        $method = $this->request->method();

        // We verify the existence of a special method DELETE, PUT
        if ($method == 'POST') {
            if ($this->special_method !== null) {
                $method = $this->special_method;
            }
        }

        // We verify the existence of the method of the request in
        // the routing collection
        if (!isset($this->routes[$method])) {
            // We verify and call function associate by 404 code
            $this->response->status(404);

            if (empty($this->error_code)) {
                $this->response->send(
                    sprintf('Cannot %s %s 404', $method, $this->request->path())
                );
            }

            return false;
        }

        $response = null;

        $error = true;

        foreach ($this->routes[$method] as $key => $route) {
            // The route must be an instance of Route
            if (!($route instanceof Route)) {
                continue;
            }

            // We launch the search of the method that arrived in the query
             // then start checking the url of the request
            if (!$route->match($this->request->path())) {
                continue;
            }

            $this->current['path'] = $route->getPath();

            // We call the action associate with the route
            $response = $route->call();
            $error = false;

            break;
        }

        // Error management
        if (!$error) {
            return $this->sendResponse($response);
        }

        // We apply the 404 error code
        $this->response->status(404);

        if (array_key_exists(404, $this->error_code)) {
            $response = Actionner::execute($this->error_code[404], []);

            return $this->sendResponse($response);
        }

        if (is_string($this->config['view.404'])) {
            $response = $this->response->render($this->config['view.404']);

            return $this->sendResponse($response);
        }

        throw new RouterException(
            sprintf('La route "%s" n\'existe pas', $this->request->path()),
            E_ERROR
        );
    }

    /**
     * Send the answer to the customer
     *
     * @param mixed $response
     * @return null
     */
    private function sendResponse($response)
    {
        if ($response instanceof ResponseInterface) {
            $response->sendContent();
        } else {
            echo $this->response->send($response);
        }
    }

    /**
     * Allows you to enable writing the X-Powered-By header
     * in the answer of the inquiry.
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
    public function rest($url, $controller_name, array $where = [])
    {
        if (!is_string($controller_name) && !is_array($controller_name)) {
            throw new ApplicationException(
                'The first parameter must be an array or a string',
                E_ERROR
            );
        }

        $ignore_method = [];

        $controller = $controller_name;

        if (is_array($controller_name)) {
            // Get controller
            if (isset($controller_name['controller'])) {
                $controller = $controller_name['controller'];

                unset($controller_name['controller']);
            }

            // Get all ignores methods
            if (isset($controller_name['ignores'])) {
                $ignore_method = $controller_name['ignores'];

                unset($controller_name['ignores']);
            }
        }

        if (is_null($controller) || !is_string($controller)) {
            throw new ApplicationException(
                "[REST] No defined controller!",
                E_ERROR
            );
        }

        // Normalize url
        $url = preg_replace('/\/+$/', '', $url);

        Resource::make($url, $controller, $where, $ignore_method);

        return $this;
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
        $this->response->status($code);

        foreach ($headers as $key => $value) {
            $this->response->addHeader($key, $value);
        }

        if ($message == null) {
            $message = 'The trial was suspended.';
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
    public function container($name = null, callable $callable = null)
    {
        if (is_null($name)) {
            return $this->capsule;
        }

        if (is_null($callable)) {
            return $this->capsule->make($name);
        }

        if (!is_callable($callable)) {
            throw new ApplicationException(
                'The second parameter must be a callable.'
            );
        }

        return $this->capsule->bind($name, $callable);
    }

    /**
     * __invoke
     *
     * This point method on the container system
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
            throw new ApplicationException('Second parameter must be pass.');
        }

        if (count($params) == 1) {
            return $this->capsule->make($params[0]);
        }

        return $this->capsule->bind($params[0], $params[1]);
    }
}
