<?php

namespace Bow\Middleware;

class TrimMiddleware
{
    /**
     * Fonction de lancement du middleware.
     *
     * @param \Bow\Http\Request $request
     * @param callable $next
     * @return boolean
     */
    public function checker($request, callable $next)
    {
        $input = array_merge($_GET, $_POST);

        foreach ($input as $key => $value) {
            $input[$key] = trim($value);
        }

        return $next();
    }
}
