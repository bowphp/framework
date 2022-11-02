<?php

declare(strict_types=1);

namespace Bow\Auth;

/**
 * @method \Policier\Token getToken()
 */
abstract class GuardContract
{
    /**
     * The define guard
     *
     * @var string
     */
    protected $guard;

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    abstract public function check();

    /**
     * Check if user is guest
     *
     * @return bool
     */
    abstract public function guest(): bool;

    /**
     * Logout
     *
     * @return bool
     */
    abstract public function logout(): bool;

    /**
     * Get authenticated user
     *
     * @return Authentication
     */
    abstract public function user(): ?Authentication;

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    abstract public function attempts(array $credentials): bool;

    /**
     * Load the a guard
     *
     * @param string $guard
     * @return GuardContract
     */
    public function guard($guard = null): GuardContract
    {
        if ($guard) {
            $this->guard = $guard;
        }

        return Auth::guard($guard);
    }
}
