<?php

namespace Bow\Middleware;

use Bow\Http\Request;

class TrimMiddleware
{
    /**
     * Fonction de lancement du middleware.
     *
     * @param Request $request
     * @param Callable $next
     * @return boolean
     */
    public function checker(Request $request, callable $next)
    {
        $input = array_merge($_GET, $_POST);

        foreach ($input as $key => $value) {
            $input[$key] = trim($value);
        }

        return $next($request);
    }
}
