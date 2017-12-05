<?php

namespace Bow\Middleware;

use Bow\Http\Input;

class ApplicationCsrfMiddleware
{
    /**
     * Fonction de lancement du middleware.
     *
     * @param  \Bow\Http\Request $request
     * @param  callable          $next
     * @return boolean
     */
    public function checker($request, callable $next)
    {
        if (!($request->isPost() || $request->isPut())) {
            return $next();
        }

        if ($request->isAjax()) {
            if ($request->getHeader('x-csrf-token') === session('_token')) {
                return $next();
            }

            response()->statusCode(401);
            return response()->send('unauthorize.');
        }

        $input = new Input();

        if ($input->get('_token', null) !== session('_token')) {
            return response('Token Mismatch');
        }

        return $next();
    }
}
