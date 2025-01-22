<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Auth\Authentication;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Exception;
use Policier\Policier;
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
     * @var ?Token
     */
    private ?Token $token = null;

    /**
     * JwtGuard constructor.
     *
     * @param array $provider
     * @param string $guard
     * @throws AuthenticationException
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
     * Check if user is authenticated
     *
     * @param array $credentials
     * @return bool
     * @throws AuthenticationException
     * @throws Exception
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
     * Check if user is authenticated
     *
     * @return bool
     * @throws Exception
     */
    public function check(): bool
    {
        $policier = $this->getPolicier();

        if (is_null($this->token)) {
            try {
                $this->token = $policier->getParsedToken();
            } catch (Exception $e) {
                return false;
            }
        }

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

        return true;
    }

    /**
     * Get the Policier instance
     *
     * @return Policier
     * @throws Exception
     */
    private function getPolicier(): Policier
    {
        if (!class_exists(Policier::class)) {
            throw new Exception('Please install bowphp/policier: composer require bowphp/policier');
        }

        $policier = Policier::getInstance();

        if (is_null($policier)) {
            throw new Exception('Please load the \Policier\Bow\PolicierConfiguration::class configuration.');
        }

        $config = (array)config('policier');

        if (!isset($config['signkey'])) {
            throw new Exception('Please set the signkey.');
        }

        return $policier;
    }

    /**
     * Make direct login
     *
     * @param Authentication $user
     * @return bool
     * @throws Exception
     */
    public function login(Authentication $user): bool
    {
        $attributes = array_merge($user->customJwtAttributes(), [
            "id" => $user->getAuthenticateUserId(),
            "logged" => true
        ]);

        $this->token = $this->getPolicier()->encode($user->getAuthenticateUserId(), $attributes);

        return true;
    }

    /**
     * Check if user is guest
     *
     * @return bool
     * @throws Exception
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Check if user is authenticated
     *
     * @return ?Authentication
     * @throws AuthenticationException
     * @throws Exception
     */
    public function user(): ?Authentication
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
     * Destruct token
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
     * @return int|string
     * @throws AuthenticationException
     */
    public function id(): int|string
    {
        if (is_null($this->token)) {
            throw new AuthenticationException("No user is logged in for get his id");
        }

        return $this->token->get('id');
    }
}
