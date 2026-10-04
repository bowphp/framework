<?php

declare(strict_types=1);

namespace Bow\Middleware;

use Bow\Http\Request;
use Bow\Security\Exception\TokenMismatch;

class CsrfMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  callable $next
     * @param  array    $args
     * @throws
     */
    public function process(Request $request, callable $next, array $args = []): mixed
    {
        foreach ($this->preventOn() as $url) {
            if ($request->is($url)) {
                return $next($request);
            }
        }

        $session = (string) $request->session()->get('_token');

        if ($request->isAjax()) {
            $provided = (string) $request->getHeader('x-csrf-token');

            // Reject empties then constant-time compare, so an unset session
            // token can no longer be matched by an absent/empty header.
            if ($session !== '' && hash_equals($session, $provided)) {
                return $next($request);
            }

            response()->status(401);

            throw new TokenMismatch(
                'The request csrf token mismatch'
            );
        }

        $provided = (string) $request->get('_token');

        // Reject empties then constant-time compare, so an unset session token
        // can no longer be matched by an absent/empty form token.
        if ($session !== '' && hash_equals($session, $provided)) {
            return $next($request);
        }

        throw new TokenMismatch(
            'The request csrf token mismatch'
        );
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
