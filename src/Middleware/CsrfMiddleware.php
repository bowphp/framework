<?php

declare(strict_types=1);

namespace Bow\Middleware;

use Bow\Http\Request;
use Bow\Security\Exception\TokenMismatch;

class CsrfMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Callable $next
     * @return bool
     * @throws
     */
    public function process(Request $request, callable $next): mixed
    {
        foreach ($this->preventOn() as $url) {
            if ($request->is($url)) {
                return $next($request);
            }
        }

        if ($request->isAjax()) {
            if ($request->getHeader('x-csrf-token') === session('_token')) {
                return $next($request);
            }

            response()->status(401);

            throw new TokenMismatch('Token Mismatch');
        }

        if ($request->get('_token') == $request->session()->get('_token')) {
            return $next($request);
        }

        throw new TokenMismatch('Token Mismatch');
    }

    /**
     * Prevent csrf action on urls
     *
     * @return array
     */
    public function preventOn(): array
    {
        return [

        ];
    }
}
