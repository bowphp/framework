<?php

namespace Bow\Auth;

class Auth
{
    /**
     * @var Auth
     */
    private $instance;

    /**
     * @var array
     */
    private $config;

    /**
     * Auth constructor.
     * 
     * @param array $provider
     */
    public function __construct(array $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Configure Auth system
     *
     * @param array $config
     */
    public static function configure(array $config)
    {
        static::$config = $config;
        static::$instance = new Auth($config['default']);
    }

    /**
     * Check if user is authenticate
     *
     * @param string $guard
     * @return Auth|null
     */
    public static function guard($guard = null)
    {
        if (is_null($guard)) {
            if (static::$instance instanceof Auth) {
                return static::$instance;
            }

            return null;
        }

        $provider = static::$config[$guard];

        return new Auth($provider);
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public static function check()
    {
        return true;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public static function user()
    {
        return true;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public static function attempts(array $credentials)
    {
        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public static function id()
    {
        return true;
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (method_exists(static::class, $method)) {
            return call_user_func_array([static::class, $method], $parameters);
        }

        throw new BadMethodCallException("La methode $methode n'existe pas", 1);
    }
}
