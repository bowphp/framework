<?php

namespace Bow\Auth;

use Bow\Security\Hash;
use Bow\Session\Session;
use Bow\Auth\Exception\AuthenticateException;

class Auth
{
    /**
     * @var Auth
     */
    private static $instance;

    /**
     * @var array
     */
    private static $config;

    /**
     * @var array
     */
    private $provider;

    /**
     * @var array
     */
    protected $credentials = [
        'email' => 'email',
        'password' => 'password'
    ];

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
        static::$config = $config;
        $provider = $config['default'];

        return static::$instance = new Auth($config[$provider]);
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
            if (static::$instance instanceof Auth) {
                return static::$instance;
            }
            return null;
        }

        if (! isset(static::$config[$guard])) {
            throw new AuthenticateException("Aucune configuration trouvé", E_ERROR);
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
        return Session::has('_auth');
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        return Session::get('_auth');
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
        $user  = $model::where('email', $credentials[$this->credentials['email']])->first();

        if (is_null($user)) {
            return false;
        }

        if (Hash::check($user->password, $credentials[$this->credentials['password']])) {
            Session::add('_auth', $user);

            return true;
        }

        return false;
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user)
    {
        Session::add('_auth', $user);

        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        return Session::get('_auth')->getAuthenticateUserId();
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

        throw new \BadMethodCallException(sprintf("La methode %s n'existe pas", $method), 1);
    }
}
