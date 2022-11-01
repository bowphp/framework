<?php

declare(strict_types=1);

namespace Bow\Auth;

use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Policier\Policier;

class JwtGuard extends GuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private $provider;

    /**
     * Defines token data
     *
     * @var string
     */
    private $token;

    /**
     * JwtGuard constructor.
     *
     * @param array $provider
     * @param string $guard
     */
    public function __construct(array $provider, string $guard = null)
    {
        if (!class_exists(Policier::class)) {
            throw new AuthenticationException('Please install bowphp/policier package.');
        }

        $this->provider = $provider;
        $this->guard = $guard;
    }

    /**
     * Check if user is authenticate
     *
     * @param array $credentials
     * @return bool
     */
    public function attempts(array $credentials)
    {
        $user = $this->makeLogin($credentials);
        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

        if (is_null($user)) {
            return false;
        }

        if (Hash::check($password, $user->{$fields['password']})) {
            $this->login($user);
            return true;
        }

        return false;
    }

    /**
     * Get the Policier instance
     *
     * @return Policier
     */
    public function getPolicier()
    {
        if (!class_exists(Policier::class)) {
            throw new \Exception('Please install: composer require bowphp/policier');
        }

        $policier = Policier::getInstance();

        if (is_null($policier)) {
            throw new \Exception('Please load the \Policier\Bow\PolicierConfiguration::class configuration.');
        }

        $config = (array) config('policier');
        
        if (is_null($config['keychain']['private']) || is_null($config['keychain']['publlic'])) {
            if (is_null($config['signkey'])) {
                $policier->setConfig(['signkey' => file_get_contents(config('security.key'))]);
            }
        }

        return $policier;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check()
    {
        $policier = $this->getPolicier();

        $token = $policier->getToken();

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
        return !$this->check();
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user()
    {
        if (is_null($this->token)) {
            if (!$this->check()) {
                throw new AuthenticationException(
                    'The token is undefined please generate some one when your log user.'
                );
            }
        }

        $result = $this->getPolicier()->decode($this->token);

        if (!isset($result['claims']['id'], $result['claims']['logged'])) {
            throw new AuthenticationException('The token payload malformed.');
        }

        $user = new $this->provider['model'];

        return $this->getUserBy($user->getKey(), $result['claims']['id']);
    }

    /**
     * Get the generated token
     *
     * @return \Policier\Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get the token string
     *
     * @return string
     */
    public function getTokenString()
    {
        return (string) $this->token;
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
            "id" => $user->getAuthenticateUserId(),
            "logged" => true
        ];

        $this->token = $this->getPolicier()->encode($user->getAuthenticateUserId(), $claims);

        return $this->token;
    }

    /**
     * Destruit token
     *
     * @return bool
     */
    public function logout()
    {
        //
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id()
    {
        $result = $this->getPolicier()->decode($this->token);

        return $result['claims']['id'];
    }
}
