<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Auth\Authentication;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Bow\Session\Exception\SessionException;
use Bow\Session\Session;

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
     * Defines the session_key
     *
     * @var string
     */
    private string $session_key;

    /**
     * SessionGuard constructor.
     *
     * @param array $provider
     * @param string $guard
     */
    public function __construct(array $provider, string $guard)
    {
        $this->provider = $provider;
        $this->guard = $guard;
        $this->session_key = '_auth_' . $this->guard;
    }

    /**
     * Check if user is authenticated
     *
     * @param array $credentials
     * @return bool
     * @throws AuthenticationException|SessionException
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
     * Check if user is authenticated
     *
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function check(): bool
    {
        return $this->getSession()->exists($this->session_key);
    }

    /**
     * Get the session instance
     *
     * @return Session
     * @throws AuthenticationException
     */
    private function getSession(): Session
    {
        $session = Session::getInstance();

        if (is_null($session)) {
            throw new AuthenticationException(
                "Please the session configuration is not load"
            );
        }

        return $session;
    }

    /**
     * Check if user is guest
     *
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     * @throws AuthenticationException|SessionException
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
     * @throws SessionException|AuthenticationException
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
     * @throws AuthenticationException|SessionException
     */
    public function id(): mixed
    {
        $user = $this->user();

        if (is_null($user)) {
            throw new AuthenticationException("No user is logged in for get his id");
        }

        return $user->getAuthenticateUserId();
    }

    /**
     * Check if user is authenticated
     *
     * @return ?Authentication
     * @throws AuthenticationException|SessionException
     */
    public function user(): ?Authentication
    {
        return $this->getSession()->get($this->session_key);
    }
}
