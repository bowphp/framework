<?php

declare(strict_types=1);

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
     * @return \Bow\Http\Redirect
     */
    public function process(Request $request, callable $next, array $guard = []): mixed
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
    public function redirectTo(): string
    {
        return '/';
    }
}
