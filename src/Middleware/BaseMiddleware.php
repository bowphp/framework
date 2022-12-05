<?php

declare(strict_types=1);

namespace Bow\Middleware;

use Bow\Http\Request;

interface BaseMiddleware
{
    /**
     * The handle method
     *
     * @param Request $request
     * @param callable $next
     * @param array $args
     * @return mixed
     */
    public function process(Request $request, callable $next, array $args = []): mixed;
}
