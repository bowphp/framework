<?php

namespace Bow\Middleware;

use Bow\Auth\Auth;
use Bow\Http\Request;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Callable  $next
     * @return boolean
     */
    public function process(Request $request, callable $next, array $guard = [])
    {
        $guard = current($guard);

        if (!$guard) {
            $guard = null;
        }

        if (Auth::getInstance()->guard($guard)->check()) {
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
