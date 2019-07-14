<?php

namespace Bow\Auth;

use Bow\Auth\Exception\AuthenticateException;
use Bow\Security\Hash;
use Bow\Session\Session;

class Auth
{
    /**
     * Defines the auth provider
     *
     * @var array
     */
    private $provider;

    /**
     * Default credentials fields
     *
     * @var array
     */
    protected $credentials = [
        'email' => 'email',
        'password' => 'password'
    ];

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
     * Auth constructor.
     *
     * @param array $provider
     * @param array $credentials
     */
    public function __construct(array $provider, $credentials = [])
    {
        $this->provider = $provider;

        $this->credentials = array_merge($credentials, $this->credentials);
    }

    /**
     * Configure Auth system
     *
     * @param array $config
     * @return Auth
     */
    public static function configure(array $config)
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        static::$config = $config;

        return static::$instance = new Auth($config[$config['default']]);
    }

    /**
     * Get Auth instance
     *
     * @return Auth
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Get the session instance
     *
     * @return Session
     */
    private static function getSession()
    {
        return Session::getInstance();
    }

    /**
     * Check if user is authenticate
     *
     * @param null|string $guard
     * @return Auth|null
     *
     * @throws AuthenticateException
     */
    public function guard($guard = null)
    {
        if (is_null($guard)) {
            return static::$instance;
        }

        if (! isset(static::$config[$guard])) {
            throw new AuthenticateException("Configuration not found for $guard guard.", E_ERROR);
        }

        $provider = static::$config[$guard];

        return new Auth($provider);
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check()
    {
        return static::getSession()->has('_auth');
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest()
    {
        return !static::getSession()->has('_auth');
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        return static::getSession()->get('_auth');
    }

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials)
    {
        $model = $this->provider['model'];

        $email = $credentials[$this->credentials['email']];
        $password = $credentials[$this->credentials['password']];

        $user = $model::where($this->credentials['email'], $email)->first();

        if (is_null($user)) {
            return false;
        }

        if (!Hash::check($user->password, $password)) {
            return false;
        }

        static::getSession()->put('_auth', $user);

        return true;
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user)
    {
        static::getSession()->add('_auth', $user);

        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        return static::getSession()->get('_auth')->getAuthenticateUserId();
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, array $parameters)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array([static::$instance, $method], $parameters);
        }

        throw new \BadMethodCallException(
            sprintf("The %s method is not found", $method),
            1
        );
    }
}
