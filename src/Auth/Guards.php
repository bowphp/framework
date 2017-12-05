<?php

namespace Bow\Auth;

class Guards
{
	/**
	 * Guards contructor
	 * 
	 * @param string|null $guard
	 */
	public function __construct($guard = null)
	{

	}

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check()
    {
        return true;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        return true;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function attempts(array $credentials)
    {
        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        return true;
    }
}