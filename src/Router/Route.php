<?php

namespace Bow\Router;

use Bow\Container\Action;
use Bow\Configuration\Loader;
use Bow\Http\Request;

class Route
{
    /**
     * The callback has launched if the url of the query has matched.
     *
     * @var callable
     */
    private $cb;

    /**
     * The road on the road set by the user
     *
     * @var string
     */
    private $path;

    /**
     * The route name
     *
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
     * The route parameter
     *
     * @var array
     */
    private $params = [];

    /**
     * List of parameters that we match
     *
     * @var array
     */
    private $match = [];

    /**
     * Additional URL validation rule
     *
     * @var array
     */
    private $with = [];

    /**
     * Application configuration
     *
     * @var Loader
     */
    private $config;

    /**
     * Route constructor
     *
     * @param string $path
     * @param callable $cb
     *
     * @throws
     */
    public function __construct($path, $cb)
    {
        $this->config = Loader::getInstance();

        $this->cb = $cb;

        $this->path = str_replace('.', '\.', $path);

        $this->match = [];
    }

    /**
     * Get the path of the current road
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the action executed on the current route
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->cb;
    }

    /**
     * Add middleware
     *
     * @param  array|string $middleware
     * @return Route
     */
    public function middleware($middleware)
    {
        $middleware = (array) $middleware;

        if (! is_array($this->cb)) {
            $this->cb = [
                'controller' => $this->cb,
                'middleware' => $middleware
            ];

            return $this;
        }


        if (!isset($this->cb['middleware'])) {
            $this->cb['middleware'] = $middleware;
        } else {
            $this->cb['middleware'] = array_merge($middleware, (array) $this->cb['middleware']);
        }

        return $this;
    }

    /**
     * Add the url rules
     *
     * @param array|string $where
     * @param string   $regex_constraint
     *
     * @return Route
     */
    public function where($where, $regex_constraint = null)
    {
        if (is_array($where)) {
            $other_rule = $where;
        } else {
            $other_rule = [$where => $regex_constraint];
        }

        $this->with = array_merge($this->with, $other_rule);

        return $this;
    }

    /**
     * Function to launch callback functions where the rule have matching.
     *
     * @return mixed
     * @throws
     */
    public function call()
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

            $tmp = (int) $this->match[$key];

            $this->params[$value] = $tmp;

            $this->match[$key] = $tmp;
        }

        return Action::getInstance()->call($this->cb, $this->match);
    }

    /**
     * To give a name to the road
     *
     * @param string $name
     */
    public function name($name)
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
     * Get the name of the route
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the settings
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Get a parameter element
     *
     * @param string $key
     * @return string|null
     */
    public function getParameter($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
    
    /**
     * Lets check if the url of the query is
     * conform to that defined by the router
     *
     * @param  string $uri
     * @return bool
     */
    public function match($uri)
    {
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

        $this->keys = end($match);

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
     * @param string $path
     * @param string $uri
     *
     * @return bool
     */
    private function checkRequestUri($path, $uri)
    {
        if (strstr($path, '?') == '?') {
            $uri = rtrim($uri, '/').'/';
        }

        // Url check and path PARSER
        $path = str_replace('~', '\\~', $path);

        if (preg_match('~^'. $path . '$~', $uri, $match)) {
            array_shift($match);

            $this->match = str_replace('/', '', $match);

            return true;
        }

        return false;
    }
}
