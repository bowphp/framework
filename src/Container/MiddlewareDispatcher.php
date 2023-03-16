<?php

namespace Bow\Container;

use Bow\Http\Request;

class MiddlewareDispatcher
{
    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var int
     */
    const PIPE_EMPTY = 1;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * Add a middleware to the runtime collection
     *
     * @param string $middleware
     * @param array $params
     * @return $this
     */
    public function pipe($middleware, array $params = [])
    {
        if (is_callable($middleware)) {
            $this->middlewares[] = $middleware;
        } else {
            $this->middlewares[] = ['class' => $middleware, 'params' => $params];
        }

        return $this;
    }

    /**
     * Start the middleware running process
     *
     * @param Request $request
     * @param array $args
     * @return mixed
     */
    public function process(Request $request, ...$args)
    {
        if (!isset($this->middlewares[$this->index]) || empty($this->middlewares)) {
            return MiddlewareDispatcher::PIPE_EMPTY;
        }

        $middleware = $this->middlewares[$this->index];

        $this->index++;

        $params = $args;

        if (is_array($middleware)) {
            if (isset($middleware['params'])) {
                $params = array_merge($args, $middleware['params']);
            }

            if (isset($middleware['class'])) {
                $middleware = [new $middleware['class'](), 'process'];
            }
        }

        $params = [$request, [$this, 'process'], $params];

        return call_user_func_array(
            $middleware,
            $params
        );
    }
}
