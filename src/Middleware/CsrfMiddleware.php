<?php

namespace Bow\Middleware;

use Bow\Http\Request;

class CsrfMiddleware
{
    /**
     * Fonction de lancement du middleware.
     *
     * @param  Request $request
     * @param  Callable $next
     * @return boolean
     */
    public function checker(Request $request, callable $next)
    {
        if (!($request->isPost() || $request->isPut())) {
            return $next($request);
        }

        if ($request->isAjax()) {
            if ($request->getHeader('x-csrf-token') === session('_token')) {
                return $next($request);
            }

            response()->statusCode(401);
            return response()->send('unauthorize.');
        }

        if ($request->get('_token', null) !== session('_token')) {
            return response('Token Mismatch');
        }

        return $next($request);
    }
}
