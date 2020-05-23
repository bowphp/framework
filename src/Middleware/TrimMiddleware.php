<?php

namespace Bow\Middleware;

use Bow\Http\Request;

class TrimMiddleware
{
    /**
     * Launch function of the middleware.
     *
     * @param Request $request
     * @param Callable $next
     * @return boolean
     */
    public function process(Request $request, callable $next)
    {
        foreach ($_GET as $key => $value) {
            $_GET[$key] = trim($value);
        }

        foreach ($_POST as $key => $value) {
            $_POST[$key] = trim($value);
        }

        return $next($request);
    }
}
