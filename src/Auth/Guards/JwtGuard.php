<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Security\Hash;
use Policier\Policier;
use Bow\Auth\Authentication;
use Bow\Auth\Guards\GuardContract;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Auth\Exception\AuthenticationException;

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
     * @var string
     */
    private ?string $token = null;

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
    public function attempts(array $credentials): bool
    {
        $user = $this->makeLogin($credentials);
        $this->token = null;
        $this->user = null;

        if (is_null($user)) {
            return false;
        }

        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

        if (Hash::check($password, $user->{$fields['password']})) {
            $this->login($user);
            return true;
        }

        return false;
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

        if (!$policier->verify($this->token)) {
            return false;
        }

        if ($policier->isExpired($this->token)) {
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
     * @return ?string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get the token string
     *
     * @return string
     */
    public function getTokenString(): string
    {
        return (string) $this->token;
    }

    /**
     * Make direct login
     *
     * @param Authentication $user
     * @return string
     */
    public function login(Authentication $user): bool
    {
        $this->token = (string) $this->getPolicier()->encode($user->getAuthenticateUserId(), [
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
        $result = $this->getPolicier()->decode($this->token);

        return $result['claims']['id'];
    }

    /**
     * Get the Policier instance
     *
     * @return Policier
     */
    private function getPolicier()
    {
        if (!class_exists(Policier::class)) {
            throw new \Exception('Please install: composer require bowphp/policier');
        }

        $policier = Policier::getInstance();

        if (is_null($policier)) {
            throw new \Exception('Please load the \Policier\Bow\PolicierConfiguration::class configuration.');
        }

        $config = (array) config('policier');

        if (count($config) > 0 && (is_null($config['keychain']['private']) || is_null($config['keychain']['public']))) {
            if (is_null($config['signkey'])) {
                $policier->setConfig(['signkey' => file_get_contents(config('security.key'))]);
            }
        }

        return $policier;
    }
}
