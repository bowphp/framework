<?php

namespace Bow\Auth;

use Bow\Auth\Exception\AuthenticationException;

class Auth
{
    /**
     * The Auth instance
     *
     * @var Auth
     */
    private static $instance;

    /**
     * The Auth configuration
     *
     * @var array
     */
    private static $config;

    /**
     * Configure Auth system
     *
     * @param array $config
     * @return GuardContract
     */
    public static function configure(array $config)
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        static::$config = $config;

        return static::guard($config['default']);
    }

    /**
     * Get Auth Instance
     *
     * @return GuardContract
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Check if user is authenticate
     *
     * @param null|string $guard
     * @return GuardContract
     *
     * @throws AuthenticationException
     */
    public static function guard($guard = null)
    {
        if (is_null($guard)) {
            return static::$instance;
        }

        if (!isset(static::$config[$guard]) || !is_array(static::$config[$guard])) {
            throw new AuthenticationException("Configuration not found for [$guard] guard.", E_ERROR);
        }

        $provider = static::$config[$guard];

        if ($provider['type'] == 'session') {
            if (static::$instance instanceof SessionGuard) {
                return static::$instance;
            }

            return static::$instance = new SessionGuard($provider);
        }

        if (static::$instance instanceof JwtGuard) {
            return static::$instance;
        }

        return static::$instance = new JwtGuard($provider);
    }

    /**
     * __callStatic
     *
     * @param string $method
     * @param array $params
     * @return GuardContract
     */
    public static function __callStatic(string $method, array $params)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array([static::$instance, $method], $params);
        }
    }
}
