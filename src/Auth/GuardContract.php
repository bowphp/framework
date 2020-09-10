<?php

namespace Bow\Auth;

abstract class GuardContract
{
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
    abstract public function guest();

    /**
     * Get authenticated user
     *
     * @return Authenticate
     */
    abstract public function user();

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    abstract public function attempts(array $credentials);

    /**
     * Load the a guard
     *
     * @param string $guard
     * @return GuardContract
     */
    public function guard($guard = null)
    {
        return Auth::guard($guard);
    }
}
