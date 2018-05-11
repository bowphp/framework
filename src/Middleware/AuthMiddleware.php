<?php

namespace Bow\Middleware;

use Bow\Auth\Auth;

class AuthMiddleware
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
        if (Auth::check()) {
            return $next();
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
