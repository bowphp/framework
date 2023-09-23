<?php

declare(strict_types=1);

namespace Bow\Middleware;

use Bow\Auth\Auth;
use Bow\Http\Request;
use Bow\Http\Redirect;
use Bow\Middleware\BaseMiddleware;

class AuthMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  callable  $next
     * @param  array $args
     * @return Redirect
     */
    public function process(Request $request, callable $next, array $args = []): mixed
    {
        if (Auth::getInstance()->check()) {
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
