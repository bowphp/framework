<?php

declare(strict_types=1);

namespace Bow\Auth;

use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Bow\Session\Session;

class SessionGuard extends GuardContract
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
     * @param string $guard
     */
    public function __construct(array $provider, string $guard = null)
    {
        $this->provider = $provider;
        $this->guard = $guard;
        $this->session_key = '_auth_' . $this->guard;
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

        if (Hash::check($password, $user->{$fields['password']})) {
            $this->getSession()->put($this->session_key, $user);
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
        return $this->getSession()->exists($this->session_key);
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->getSession()->exists($this->session_key);
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        return $this->getSession()->get($this->session_key);
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user)
    {
        $this->getSession()->add($this->session_key, $user);

        return true;
    }

    /**
     * Make direct logout
     *
     * @return bool
     */
    public function logout()
    {
        $this->getSession()->remove($this->session_key);

        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        return $this->getSession()->get($this->session_key)->getAuthenticateUserId();
    }
}
