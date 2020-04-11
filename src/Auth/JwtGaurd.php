<?php

namespace Bow\Auth;

use Bow\Auth\Exception\AuthenticateException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Bow\Session\Session;
use Policier\Policier;

class JwtGaurd implements AuthGuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private $provider;

    /**
     * Defines policier instance
     *
     * @var Policier
     */
    private $policier;

    /**
     * Defines token data
     *
     * @var string
     */
    private $token;

    /**
     * JwtGaurd constructor.
     *
     * @param array $provider
     */
    public function __construct(array $provider)
    {
        $this->provider = $provider;
        $this->policier = Policier::getInstance();
    }

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials)
    {
        $user = $this->makeLogin();

        if (is_null($user)) {
            return false;
        }

        if (!Hash::check($user->password, $password)) {
            return false;
        }

        return true;
    }

    /**
     * Get the session instance
     *
     * @return Session
     */
    private static function getPolicier()
    {
        return Policier::getInstance();
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check()
    {
        $bearer = request()->getHeader('Authorization');

        if (is_null($bearer) || !preg_match('/^Bearer\s+(.+)/', trim($bearer), $match)) {
            return false;
        }

        $token = trim(end($match));

        if (!$policier->verify($token)) {
            return false;
        }

        if ($policier->isExpired($token)) {
            return false;
        }

        $this->token = $token;

        $user = $this->user();

        return $user !== null;
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest()
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
        if (is_null($this->token)) {
            throw new AuthenticateException('The token is undefined please generate some one when your log user.');
        }

        $result = $policier->decode($this->token);

        if (!isset($result['claims']['email'], $result['claims']['id'], $result['claims']['logged'])) {
            throw new AuthenticateException('The token payload malformed.');
        }

        return $this->getUserBy('email', $result['claims']['email']);
    }

    /**
     * Get the generated token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Make direct login
     *
     * @param mixed $user
     * @return bool
     */
    public function login(Authentication $user)
    {
        $claims = [
          "email" => $user->email,
          "id" => $user->getAuthenticateUserId(),
          "logged" => true
        ];

        $this->token = $this->policier->encode($user->getAuthenticateUserId(), $claims);

        return $this->token;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        $result = $policier->decode($this->token);

        return $result['claims']['id'];
    }
}
