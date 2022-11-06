<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Auth\Auth;
use Bow\Auth\Authentication;

/**
 * @method ?string getToken()
 */
abstract class GuardContract
{
    /**
     * The define guard
     *
     * @var string
     */
    protected string $guard;

    /**
     * Check the user id
     *
     * @return mixed
     */
    abstract public function id(): mixed;

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    abstract public function check(): bool;

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
     * Logout
     *
     * @param Authentication $user
     * @return bool
     */
    abstract public function login(Authentication $user): bool;

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
     * Get the guard name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->guard;
    }

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
