<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Security\Hash;
use Policier\Policier;
use Bow\Auth\Authentication;
use Bow\Auth\Guards\GuardContract;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Auth\Exception\AuthenticationException;
use Policier\Token;

class JwtGuard extends GuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private array $provider = [];

    /**
     * Defines token data
     *
     * @var Token
     */
    private ?Token $token = null;

    /**
     * JwtGuard constructor.
     *
     * @param array $provider
     * @param string $guard
     */
    public function __construct(array $provider, string $guard)
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
    public function attempts(array $credentials): bool
    {
        $user = $this->makeLogin($credentials);
        $this->token = null;

        if (is_null($user)) {
            return false;
        }

        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

        if (!Hash::check($password, $user->{$fields['password']})) {
            return false;
        }

        $this->login($user);

        return true;
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function check(): bool
    {
        if (is_null($this->token)) {
            return false;
        }

        $policier = $this->getPolicier();

        if (!$policier->verify($this->token->getValue())) {
            return false;
        }

        if ($policier->isExpired($this->token->getValue())) {
            return false;
        }

        $user = $this->user();

        return $user !== null;
    }

    /**
     * Check if user is guest
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Check if user is authenticate
     *
     * @return bool
     */
    public function user(): Authentication
    {
        if (!$this->check()) {
            throw new AuthenticationException(
                'Token is not set, please generate one when user is logged in.'
            );
        }

        if (!($this->token->has('id') && $this->token->has('logged'))) {
            throw new AuthenticationException('The token payload malformed.');
        }

        $user = new $this->provider['model']();

        return $this->getUserBy($user->getKey(), $this->token->get("id"));
    }

    /**
     * Get the generated token
     *
     * @return ?Token
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }

    /**
     * Make direct login
     *
     * @param Authentication $user
     * @return string
     */
    public function login(Authentication $user): bool
    {
        $this->token = $this->getPolicier()->encode($user->getAuthenticateUserId(), [
            "id" => $user->getAuthenticateUserId(),
            "logged" => true
        ]);

        return true;
    }

    /**
     * Destruit token
     *
     * @return bool
     */
    public function logout(): bool
    {
        return true;
    }

    /**
     * Get the user id
     *
     * @return bool
     */
    public function id(): mixed
    {
        if (is_null($this->token)) {
            throw new AuthenticationException("No user is logged in for get his id");
        }

        $token = $this->getPolicier()->decode($this->token);

        return $token->get('id');
    }

    /**
     * Get the Policier instance
     *
     * @return Policier
     */
    private function getPolicier()
    {
        if (!class_exists(Policier::class)) {
            throw new \Exception('Please install bowphp/policier: composer require bowphp/policier');
        }

        $policier = Policier::getInstance();

        if (is_null($policier)) {
            throw new \Exception('Please load the \Policier\Bow\PolicierConfiguration::class configuration.');
        }

        $config = (array) config('policier');

        if (!isset($config['signkey']) || is_null($config['signkey'])) {
            throw new \Exception('Please set the signkey.');
        }

        return $policier;
    }
}
