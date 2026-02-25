<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Configuration\Loader;
use Bow\Container\Compass;

class Route
{
    /**
     * The callback to execute if the route matches.
     *
     * @var mixed
     */
    private mixed $callback;

    /**
     * The route path pattern
     *
     * @var string
     */
    private string $path = '';

    /**
     * The domain pattern for the route (optional)
     *
     * @var string|null
     */
    private ?string $domain = null;

    /**
     * The route name
     *
     * @var null|string
     */
    private ?string $name = null;

    /**
     * Parameter keys extracted from the path
     *
     * @var array
     */
    private array $keys = [];

    /**
     * Route parameters
     *
     * @var array
     */
    private array $params = [];

    /**
     * Matched values from the URI
     *
     * @var array
     */
    private array $match = [];

    /**
     * Additional URL validation rules
     *
     * @var array
     */
    private array $with = [];

    /**
     * Application configuration
     *
     * @var Loader
     */
    private Loader $config;

    /**
     * Route constructor
     *
     * @param string $path
     * @param mixed  $cb
     *
     * @throws
     */
    public function __construct(string $path, mixed $callback)
    {
        $this->config = Loader::getInstance();
        $this->callback = $callback;
        $this->path = str_replace('.', '\\.', $path);
        $this->match = [];
    }

    /**
     * Get the action executed on the current route
     *
     * @return mixed
     */
    public function getAction(): mixed
    {
        return $this->callback;
    }

    /**
     * Add middleware
     *
     * @param  array|string $middleware
     * @return Route
     */
    public function middleware(array|string $middleware): Route
    {
        $middleware = (array)$middleware;
        if (!is_array($this->callback)) {
            $this->callback = [
                'controller' => $this->callback,
                'middleware' => $middleware
            ];
            return $this;
        }
        $this->callback['middleware'] = !isset($this->callback['middleware'])
            ? $middleware
            : array_merge((array)$this->callback['middleware'], $middleware);
        return $this;
    }

    /**
     * Set the domain pattern for the route
     *
     * @param string $domainPattern
     * @return $this
     */
    public function withDomain(string $domainPattern): self
    {
        $this->domain = $domainPattern;
        return $this;
    }

    /**
     * Add the url rules
     *
     * @param  array|string $where
     * @param  string|null  $regex_constraint
     * @return Route
     */
    public function where(array|string $where, ?string $regex_constraint = null): Route
    {
        $other_rule = is_array($where) ? $where : [$where => $regex_constraint];

        $this->with = array_merge($this->with, $other_rule);

        return $this;
    }

    /**
     * Function to launch callback functions where the rule have matching.
     *
     * @return mixed
     * @throws
     */
    public function call(): mixed
    {
        // Association of parameters at the request
        foreach ($this->keys as $key => $value) {
            if (!isset($this->match[$key])) {
                continue;
            }

            if (!is_int($this->match[$key])) {
                $this->params[$value] = urldecode($this->match[$key]);
                continue;
            }

            $tmp = (int)$this->match[$key];
            $this->params[$value] = $tmp;
            $this->match[$key] = $tmp;
        }

        return Compass::getInstance()->call($this->callback, $this->match);
    }

    /**
     * To give a name to the road
     *
     * @param  string $name
     * @return Route
     */
    public function name(string $name): Route
    {
        $this->name = $name;

        $routes = (array)$this->config['app.routes'];

        $this->config['app.routes'] = array_merge(
            $routes,
            [$name => $this->getPath()]
        );

        return $this;
    }

    /**
     * Get the path of the current road
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the name of the route
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the settings
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->params;
    }

    /**
     * Get a parameter element
     *
     * @param  string $key
     * @return ?string
     */
    public function getParameter(string $key): ?string
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Lets check if the url of the query is
     * conform to that defined by the router
     *
     * @param  string $uri
     * @return bool
     */
    public function match(string $uri, ?string $host = null): bool
    {
        // If a domain constraint is set, check the host
        if ($this->domain !== null && $host !== null) {
            // Convert domain pattern to regex (support wildcards like *.example.com)
            $pattern = str_replace(['.', '*'], ['\\.', '.*'], $this->domain);
            if (!preg_match('/^' . $pattern . '$/i', $host)) {
                return false;
            }
        }

        // Normalization of the url of the navigator.
        if (preg_match('~(.*)/$~', $uri, $match)) {
            $uri = end($match);
        }

        // Normalization of the path defined by the programmer.
        if (preg_match('~(.*)/$~', $this->path, $match)) {
            $this->path = end($match);
        }

        // We go straight back to gain performance.
        if ($this->path === $uri) {
            return true;
        }

        // We check the length of the path defined by the programmer
        // with that of the current url in the user's browser.
        $path = implode('', preg_split('/(\/:[a-z0-9-_]+\?)/', $this->path));

        if (count(explode('/', $path)) != count(explode('/', $uri))) {
            if (count(explode('/', $this->path)) != count(explode('/', $uri))) {
                return false;
            }
        }

        // Copied of url
        $path = $uri;

        // In case the developer did not add of constraint on captured variables
        if (empty($this->with)) {
            $path = preg_replace('~:\w+(\?)?~', '([^\s]+)$1', $this->path);

            preg_match_all('~:([a-z-0-9_-]+?)\?~', $this->path, $this->keys);

            $this->keys = end($this->keys);

            return $this->checkRequestUri($path, $uri);
        }

        // In case the developer has added constraints
        // on the captured variables
        if (!preg_match_all('~:([\w]+)?~', $this->path, $match)) {
            return $this->checkRequestUri($path, $uri);
        }

        $tmp_path = $this->path;

        $this->keys = (array)end($match);

        // Association of criteria personalized.
        foreach ($this->keys as $key) {
            if (array_key_exists($key, $this->with)) {
                $tmp_path = preg_replace('~:' . $key . '~', '(' . $this->with[$key] . ')', $tmp_path);
            }
        }

        // Clear the custom criteria association table.
        $this->with = [];

        // In the case where the different path one recovers, one recovers the one in $tmp_path
        if ($tmp_path != $this->path) {
            $path = $tmp_path;
        }

        // Url check and path PARSER
        return $this->checkRequestUri($path, $uri);
    }

    /**
     * Check the url for the search
     *
     * @param  string $path
     * @param  string $uri
     * @return bool
     */
    private function checkRequestUri(string $path, string $uri): bool
    {
        if (strstr($path, '?') == '?') {
            $uri = rtrim($uri, '/') . '/';
        }

        // Url check and path PARSER
        $path = str_replace('~', '\\~', $path);

        if (!preg_match('~^' . $path . '$~', $uri, $match)) {
            return false;
        }

        array_shift($match);

        $this->match = array_map(fn($v) => is_string($v) ? str_replace('/', '', $v) : $v, $match);

        return true;
    }
}
