<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Security\Hash;
use Bow\Session\Session;
use Bow\Auth\Authentication;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Guards\GuardContract;
use Bow\Auth\Traits\LoginUserTrait;

class SessionGuard extends GuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private array $provider;

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
    public function attempts(array $credentials): bool
    {
        $user = $this->makeLogin($credentials);

        if (is_null($user)) {
            return false;
        }

        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

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
    private function getSession(): Session
    {
        $session = Session::getInstance();

        if (is_null($session)) {
            throw new AuthenticationException("Please the session configuration is not load");
        }

        return $session;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->getSession()->exists($this->session_key);
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Check if user is authenticate
     *
     * @return ?Authentication
     */
    public function user(): ?Authentication
    {
        return $this->getSession()->get($this->session_key);
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user): bool
    {
        $this->getSession()->add($this->session_key, $user);

        return true;
    }

    /**
     * Make direct logout
     *
     * @return bool
     */
    public function logout(): bool
    {
        $this->getSession()->remove($this->session_key);

        return true;
    }

    /**
     * Get the user id
     *
     * @return mixed
     */
    public function id(): mixed
    {
        $user = $this->user();

        if (is_null($user)) {
            throw new AuthenticationException("No user is logged in for get his id");
        }

        return $user->getAuthenticateUserId();
    }
}
