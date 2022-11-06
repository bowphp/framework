<?php

declare(strict_types=1);

namespace Bow\Auth;

use Bow\Auth\Guards\JwtGuard;
use Bow\Auth\Guards\SessionGuard;
use Bow\Auth\Guards\GuardContract;
use Bow\Auth\Exception\AuthenticationException;

class Auth
{
    /**
     * The Auth instance
     *
     * @var GuardContract
     */
    private static ?GuardContract $instance = null;

    /**
     * The Auth configuration
     *
     * @var array
     */
    private static array $config;

    /**
     * The current guard
     *
     * @var string
     */
    private static string $guard;

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
     * @return ?GuardContract
     */
    public static function getInstance(): ?GuardContract
    {
        return static::$instance;
    }

    /**
     * Check if user is authenticate
     *
     * @param null|string $guard
     * @return GuardContract
     * @throws AuthenticationException
     */
    public static function guard(?string $guard = null): GuardContract
    {
        if (is_null($guard)) {
            return static::$instance;
        }

        if (!isset(static::$config[$guard]) || !is_array(static::$config[$guard])) {
            throw new AuthenticationException(
                "Configuration not found for [$guard] guard.",
                E_ERROR
            );
        }

        $provider = static::$config[$guard];

        // Load the session provider
        if ($provider['type'] == 'session') {
            if (static::$instance instanceof SessionGuard) {
                if (static::$guard == $guard) {
                    return static::$instance;
                }
            }

            static::$guard = $guard;
            return static::$instance = new SessionGuard($provider, $guard);
        }

        // Load the jwt session provider
        if (static::$instance instanceof JwtGuard) {
            if (static::$guard == $guard) {
                return static::$instance;
            }
        }

        static::$guard = $guard;
        return static::$instance = new JwtGuard($provider, $guard);
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
