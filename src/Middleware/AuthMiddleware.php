<?php

namespace Bow\Middleware;

use Bow\Auth\Auth;
use Bow\Http\Request;

class AuthMiddleware
{
    /**
     * Launch function of the middleware.
     *
     * @param  Request $request
     * @param  Callable  $next
     * @return boolean
     */
    public function process(Request $request, callable $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        return redirect($this->redirectTo());
    }

    /**
     * Redirect to
     *
     * @return string
     */
    public function redirectTo()
    {
        return '/';
    }
}
