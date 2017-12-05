<?php

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
     * @param string $middleware
     * @return $this
     */
    public function pipe($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * @param $request
     * @return mixed
     */
    public function process($request)
    {
        if (!isset($this->middlewares[$this->index])) {
            return null;
        }

        $middleware = $this->middlewares[$this->index];
        $this->index++;

        return call_user_func_array([new $middleware, 'checker'], [$request, [$this, 'process']]);
    }
}
