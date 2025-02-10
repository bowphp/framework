<?php

declare(strict_types=1);

namespace Bow\Application;

use Bow\Application\Exception\ApplicationException;
use Bow\Configuration\Loader;
use Bow\Container\Capsule;
use Bow\Container\Compass;
use Bow\Contracts\ResponseInterface;
use Bow\Http\Exception\BadRequestException;
use Bow\Http\Exception\HttpException;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Router\Exception\RouterException;
use Bow\Router\Resource;
use Bow\Router\Route;
use Bow\Router\Router;
use ReflectionException;

class Application extends Router
{
    /**
     * The Application instance
     *
     * @var ?Application
     */
    private static ?Application $instance = null;
    /**
     * The Capsule instance
     *
     * @var Capsule
     */
    private Capsule $capsule;
    /**
     * The booting flag
     *
     * @var bool
     */
    private bool $booted = false;
    /**
     * The HTTP Request
     *
     * @var Request
     */
    private Request $request;

    /**
     * The HTTP Response
     *
     * @var Response
     */
    private Response $response;

    /**
     * The Configuration Loader instance
     *
     * @var Loader
     */
    private Loader $config;

    /**
     * This defines if the X-powered-By header must be put in response
     *
     * @var bool
     */
    private bool $disable_powered_by = false;

    /**
     * Application constructor
     *
     * @param Request $request
     * @param Response $response
     * @throws BadRequestException
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->capsule = Capsule::getInstance();

        $this->capsule->instance('response', $response);
        $this->capsule->instance('request', $request);
        $this->capsule->instance('app', $this);

        $this->request->capture();

        parent::__construct($request->method(), $request->get('_method'));
    }

    /**
     * Get container
     *
     * @return Capsule
     */
    public function getContainer(): Capsule
    {
        return $this->capsule;
    }

    /**
     * Check if it is running on php cli
     *
     * @return bool
     */
    public function isRunningOnCli(): bool
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Launcher of the application
     *
     * @return bool
     * @throws ReflectionException
     * @throws RouterException
     */
    public function send(): bool
    {
        if ($this->config->isCli()) {
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

        $response = null;
        $resolved = false;

        foreach ($routes[$method] ?? [] as $route) {
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
            $this->sendResponse($response);
            return true;
        }

        // We apply the 404 error code
        $this->response->status(404);

        if (!array_key_exists(404, $this->error_code)) {
            throw new RouterException(
                sprintf('Route "%s" not found', $this->request->path())
            );
        }

        $response = Compass::getInstance()->execute($this->error_code[404], []);

        $this->sendResponse($response, 404);

        return false;
    }

    /**
     * Send the answer to the customer
     *
     * @param mixed $response
     * @param int $code
     * @return void
     */
    private function sendResponse(mixed $response, int $code = 200): void
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
    public function disablePoweredByMention(): void
    {
        $this->disable_powered_by = true;
    }

    /**
     * Make the REST API base on route and resource controller.
     *
     * @param string $url
     * @param string|array $controller_name
     * @param array $where
     * @return Application
     *
     * @throws ApplicationException
     */
    public function rest(string $url, string|array $controller_name, array $where = []): Application
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

        if (!is_string($controller)) {
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
     * Build the application
     *
     * @param Request $request
     * @param Response $response
     * @return Application
     * @throws BadRequestException
     */
    public static function make(Request $request, Response $response): Application
    {
        if (is_null(static::$instance)) {
            static::$instance = new Application($request, $response);
        }

        return static::$instance;
    }

    /**
     * Abort application
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     *
     * @throws HttpException
     */
    public function abort(int $code = 500, string $message = '', array $headers = []): void
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
     * Build dependence
     *
     * @param ?string $name
     * @param ?callable $callable
     * @return mixed
     * @throws ApplicationException
     */
    public function container(?string $name = null, ?callable $callable = null): mixed
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

        $this->capsule->bind($name, $callable);

        return $this;
    }

    /**
     * Configuration Association
     *
     * @param Loader $config
     * @return void
     */
    public function bind(Loader $config): void
    {
        $this->config = $config;

        if (is_string($config['app']['root'])) {
            $this->setBaseRoute($config['app']['root']);
        }

        // We activate the auto csrf switcher
        $this->setAutoCsrf((bool)($config['app']['auto_csrf'] ?? false));

        $this->capsule->instance('config', $config);

        $this->boot();
    }

    /**
     * Boot the application
     *
     * @return void
     */
    private function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->config->boot();

        $this->booted = true;
    }

    /**
     * __invoke
     *
     * This point method on the container system
     *
     * @param array $params
     * @return mixed
     * @throws ApplicationException
     */
    public function __invoke(...$params): mixed
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
