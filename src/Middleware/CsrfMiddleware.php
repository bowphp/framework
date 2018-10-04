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
        if (in_array($request->uri(), $this->preventOn())) {
            return $next($request);
        }

        if ($request->isAjax()) {
            if ($request->getHeader('x-csrf-token') === session('_token')) {
                return $next($request);
            }

            response()->statusCode(401);

            return response()->send('unauthorize.');
        }

        dd($request->get('_token'), $request->session());

        if ($request->get('_token') == $request->session()->get('_token')) {
            return $next($request);
        }

        return response('Token Mismatch');
    }

    /**
     * Prevent csrf action on urls
     *
     * @return array
     */
    public function preventOn()
    {
        return [

        ];
    }
}
