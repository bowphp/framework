<?php

namespace Bow\Firewall;

use Bow\Http\Input;

class ApplicationCsrfFirewall
{
    /**
     * Fonction de lancement du firewall.
     *
     * @param \Bow\Http\Request $request
     * @param \Closure $next
     * @return boolean
     */
    public function checker($request, \Closure $next)
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