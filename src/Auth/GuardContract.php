<?php

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
        if ($guard) {
            $this->guard = $guard;
        }

        return Auth::guard($guard);
    }
}
