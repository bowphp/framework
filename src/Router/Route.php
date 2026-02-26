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
        $this->path = $path;
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
     * @param string $domain_pattern
     * @return $this
     */
    public function withDomain(string $domain_pattern): self
    {
        $this->domain = $domain_pattern;

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
            if (!isset($this->match[$key]) || $this->match[$key] === null) {
                $this->params[$value] = null;
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

        // Filter out null values before passing to Compass
        $args = array_filter($this->match, fn($v) => $v !== null);

        return Compass::getInstance()->call($this->callback, $args);
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

        $routes = (array) $this->config['app.routes'];

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
        // If a domain constraint is set, check the host and capture params
        if ($this->domain !== null && $host !== null) {
            $domain_param_names = [];
            $domain_pattern = $this->domain;
            // Build regex for domain with parameter capture (supports :param and <param>)
            $domain_pattern = preg_replace_callback(
                '/(:([a-zA-Z0-9_]+)|<([a-zA-Z0-9_]+)>)/',
                function ($m) use (&$domain_param_names) {
                    $name = $m[2] !== '' ? $m[2] : $m[3];
                    $domain_param_names[] = $name;
                    return '([^.]+)';
                },
                $domain_pattern
            );
            // Escape dots and handle wildcards
            $domain_pattern = str_replace(['.', '*'], ['\\.', '[^.]+'], $domain_pattern);
            if (!preg_match('~^' . $domain_pattern . '$~i', $host, $domain_matches)) {
                return false;
            }
            // Store domain params
            array_shift($domain_matches);
            foreach ($domain_param_names as $i => $name) {
                if (isset($domain_matches[$i])) {
                    $this->params[$name] = $domain_matches[$i];
                }
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

        // Check segment count (accounting for optional params)
        $route_segments = explode('/', trim($this->path, '/'));
        $uri_segments = explode('/', trim($uri, '/'));
        $optional_count = 0;
        foreach ($route_segments as $seg) {
            if (preg_match('/^(:[a-zA-Z0-9_]+\?|<[a-zA-Z0-9_]+\?>)$/', $seg)) {
                $optional_count++;
            }
        }
        $route_required = count($route_segments) - $optional_count;
        $uri_count = count($uri_segments);
        if ($uri_count < $route_required || $uri_count > count($route_segments)) {
            return false;
        }

        // Robust regex builder for path parameters (supports :param, <param>, optional, required)
        if (empty($this->with)) {
            $param_names = [];
            $regex_parts = [];
            foreach ($route_segments as $seg) {
                /** Optional :param? or <param?> */
                if (preg_match('/^:([a-zA-Z0-9_]+)\?$/', $seg, $m) || preg_match('/^<([a-zA-Z0-9_]+)\?>$/', $seg, $m)) {
                    $param_names[] = $m[1];
                    $regex_parts[] = '(?:/([^/]+))?';
                }
                // Required :param or <param>
                elseif (preg_match('/^:([a-zA-Z0-9_]+)$/', $seg, $m) || preg_match('/^<([a-zA-Z0-9_]+)>$/', $seg, $m)) {
                    $param_names[] = $m[1];
                    $regex_parts[] = '/([^/]+)';
                }
                // Static segment
                else {
                    $regex_parts[] = '/' . preg_quote($seg, '~');
                }
            }
            $regex = '~^' . implode('', $regex_parts) . '$~';
            $this->keys = $param_names;
            // Build URI with leading slash for matching
            $normalized_uri = '/' . implode('/', $uri_segments);
            if (!preg_match($regex, $normalized_uri, $matches)) {
                return false;
            }
            array_shift($matches);
            // Pad missing optionals with null
            $matches = array_pad($matches, count($this->keys), null);
            $this->match = $matches;
            return true;
        }

        // In case the developer has added constraints
        // on the captured variables
        if (!preg_match_all('~:([\w]+)?~', $this->path, $match)) {
            return $this->checkRequestUri($this->path, $uri);
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
