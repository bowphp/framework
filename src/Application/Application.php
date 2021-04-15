<?php

namespace Bow\Application;

use Bow\Application\Exception\ApplicationException;
use Bow\Container\Capsule;
use Bow\Container\Actionner;
use Bow\Configuration\Loader;
use Bow\Contracts\ResponseInterface;
use Bow\Http\Exception\HttpException;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Router\Exception\RouterException;
use Bow\Router\Resource;
use Bow\Router\Router;
use Bow\Router\Route;

class Application extends Router
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
     * The Application instance
     *
     * @var Application
     */
    private static $instance;

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
    private $disable_powered_by = false;

    /**
     * Application constructor
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function __construct(Request $request, Response $response)
    {
        $this->request = $request;

        $this->response = $response;

        $this->capsule = Capsule::getInstance();

        $this->capsule->instance('request', $request);

        $this->capsule->instance('response', $response);

        $this->capsule->instance('app', $this);

        parent::__construct($request->method(), $request->get('_method'));
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

        if (is_string($config['app']['root'])) {
            $this->setBaseRoute($config['app']['root']);
        }

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

        // We add of the X-Powered-By header when disable_powered_by is true
        if (!$this->disable_powered_by) {
            $this->response->addHeader('X-Powered-By', 'Bow Framework');
        }

        $this->prefix = '';

        $method = $this->request->method();

        // We verify the existence of a special method DELETE, PUT
        if ($method == 'POST') {
            if ($this->hasSpecialMethod()) {
                $method = $this->getSpecialMethod();
            }
        }

        // We verify the existence of the method of the request in
        // the routing collection
        $routes = $this->getRoutes();

        if (!isset($routes[$method])) {
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
        $resolved = false;

        foreach ($routes[$method] as $key => $route) {
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
            $resolved = true;

            break;
        }

        // Error management
        if ($resolved) {
            return $this->sendResponse($response);
        }

        // We apply the 404 error code
        $this->response->status(404);

        if (array_key_exists(404, $this->error_code)) {
            $response = Actionner::getInstance()->execute($this->error_code[404], []);

            return $this->sendResponse($response, 404);
        }

        if (is_string($this->config['view.404']) && file_exists($this->config['view.404'])) {
            $view = $this->container('view');
            $response = $this->container('view')->parse($this->config['view.404']);

            return $this->sendResponse($response, 404);
        }

        throw new RouterException(
            sprintf('Route "%s" not found', $this->request->path())
        );
    }

    /**
     * Send the answer to the customer
     *
     * @param mixed $response
     * @param int $code
     * @return null
     */
    private function sendResponse($response, $code = 200)
    {
        if ($response instanceof ResponseInterface) {
            $response->sendContent();
        } else {
            echo $this->response->send($response, $code);
        }
    }

    /**
     * Allows you to enable writing the X-Powered-By header
     * in the answer of the inquiry.
     *
     * @return void
     */
    public function disablePoweredByMention()
    {
        $this->disable_powered_by = true;
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
