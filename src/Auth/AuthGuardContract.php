<?php

namespace Bow\Auth;

interface AuthGuardContract
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
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user();

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials);
}
