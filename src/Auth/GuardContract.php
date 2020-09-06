<?php

namespace Bow\Auth;

interface GuardContract
{
    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check();

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest();

    /**
     * Get authenticated user
     *
     * @return Authenticate
     */
    public function user();

    /**
     * Load the a guard
     *
     * @return GuardContract
     */
    public function guard();

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials);
}
