<?php

namespace Bow\Auth;

use Bow\Auth\Exception\AuthenticateException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Bow\Session\Session;

class SessionGuard implements GuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private $provider;

    /**
     * SessionGuard constructor.
     *
     * @param array $provider
     */
    public function __construct(array $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials)
    {
        $user = $this->makeLogin($credentials);
        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

        if (is_null($user)) {
            return false;
        }

        if (Hash::check($password, $user->${$fields['password']})) {
            $this->getSession()->put('_auth', $user);
            return true;
        }

        return false;
    }

    /**
     * Get the session instance
     *
     * @return Session
     */
    private function getSession()
    {
        return Session::getInstance();
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check()
    {
        return $this->getSession()->has('_auth');
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->getSession()->has('_auth');
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        return $this->getSession()->get('_auth');
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user)
    {
        $this->getSession()->add('_auth', $user);

        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        return $this->getSession()->get('_auth')->getAuthenticateUserId();
    }
}
