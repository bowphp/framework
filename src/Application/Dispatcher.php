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
     * @return $this
     */
    public function pipe($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Lance le procéssus d'execution de middleware
     *
     * @param Request $request
     * @return mixed
     */
    public function process(Request $request)
    {
        if (!isset($this->middlewares[$this->index])) {
            return null;
        }

        $middleware = $this->middlewares[$this->index];
        $this->index++;

        if (!is_callable($middleware)) {
            $middleware = [new $middleware, 'checker'];
        }

        return call_user_func_array($middleware, [$request, [$this, 'process']]);
    }
}
