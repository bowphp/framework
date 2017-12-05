<?php

namespace Bow\Security;

use Bow\Session\Session;
use Bow\Support\Str;

class Tokenize
{
    /**
     * @static int
     */
    private static $expirate_at;

    /**
     * Createur de token csrf
     *
     * @param  int $time
     * @return bool
     */
    public static function makeCsrfToken($time = null)
    {
        if (Session::has('__bow.csrf')) {
            return true;
        }

        if (is_int($time)) {
            static::$expirate_at = $time;
        }

        $token = static::make();

        Session::add(
            '__bow.csrf',
            [
            'token' => $token,
            'expirate' => time() + static::$expirate_at,
            'field' => '<input type="hidden" name="_token" value="' . $token .'"/>'
            ]
        );

        Session::add('_token', $token);

        return true;
    }

    /**
     * Générer une clé crypté en md5
     *
     * @return string
     */
    public static function make()
    {
        $salt = date('Y-m-d H:i:s', time() - 10000) . uniqid(rand(), true);
        $token = base64_encode(base64_encode(openssl_random_pseudo_bytes(6)) . $salt);
        return Str::slice(hash('sha256', $token), 1, 62);
    }

    /**
     * Retourne un token csrf générer
     *
     * @param  int $time
     * @return mixed
     */
    public static function csrf($time = null)
    {
        static::makeCsrfToken($time);
        return Session::get('__bow.csrf');
    }

    /**
     * Vérifie si le token en expire
     *
     * @param int $time le temps d'expiration
     *
     * @return boolean
     */
    public static function csrfExpirated($time = null)
    {
        if (Session::has('__bow.csrf')) {
            return false;
        }

        if ($time === null) {
            $time = time();
        }

        $csrf = Session::get('__bow.csrf');

        if ($csrf['expirate'] >= (int) $time) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si token csrf est valide
     *
     * @param string $token  le token a
     *                       vérifié
     * @param bool   $strict le niveau de
     *                       vérification
     *
     * @return boolean
     */
    public static function verify($token, $strict = false)
    {

        if (!Session::has('__bow.csrf')) {
            return false;
        }

        $csrf = Session::get('__bow.csrf');

        if ($token !== $csrf['token']) {
            return false;
        }

        $status = true;

        if ($strict) {
            $status = $status && static::CsrfExpirated(time());
        }

        return $status;
    }

    /**
     * Détruie le token
     */
    public static function clear()
    {
        Session::remove('__bow.csrf');
        Session::remove('_token');
    }
}
