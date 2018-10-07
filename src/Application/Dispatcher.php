<?php

namespace Bow\Application;

use Bow\Http\Request;

class Dispatcher
{
    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * Ajout un middleware à la collection d'execution
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
     * Lance le procéssus d'execution de middleware
     *
     * @param Request $request
     * @param array $args
     * @return mixed
     */
    public function process(Request $request, ...$args)
    {
        if (!isset($this->middlewares[$this->index])) {
            return null;
        }

        $middleware = $this->middlewares[$this->index];

        $this->index++;

        $params = $args;

        if (is_array($middleware)) {
            $params = array_merge($args, $middleware['params']);

            $middleware = [new $middleware['class'], 'process'];
        }

        $params = [$request, [$this, 'process'], $params];

        return call_user_func_array(
            $middleware,
            $params
        );
    }
}
