<?php

declare(strict_types=1);

namespace Bow\Auth\Guards;

use Bow\Auth\Authentication;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Traits\LoginUserTrait;
use Bow\Security\Hash;
use Bow\Session\Cookie;
use Bow\Session\Exception\SessionException;
use Bow\Session\Session;

class SessionGuard extends GuardContract
{
    use LoginUserTrait;

    /**
     * Defines the auth provider
     *
     * @var array
     */
    private array $provider;

    /**
     * Defines the session_key
     *
     * @var string
     */
    private string $session_key;

    /**
     * SessionGuard constructor.
     *
     * @param array  $provider
     * @param string $guard
     */
    public function __construct(array $provider, string $guard)
    {
        $this->provider = $provider;
        $this->guard = $guard;
        $this->session_key = '_auth_' . $this->guard;
    }

    /**
     * Check if user is authenticated
     *
     * @param  array $credentials
     * @param  bool  $remember
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function attempts(array $credentials, bool $remember = false): bool
    {
        $user = $this->makeLogin($credentials);

        if (is_null($user)) {
            return false;
        }

        $fields = $this->provider['credentials'];
        $password = $credentials[$fields['password']];

        if (Hash::check($password, $user->{$fields['password']})) {
            $this->getSession()->put($this->session_key, $user);

            if ($remember) {
                $this->setRememberCookie($user);
            }

            return true;
        }

        return false;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function check(): bool
    {
        return $this->getSession()->exists($this->session_key)
            || $this->attemptRememberLogin();
    }

    /**
     * Get the session instance
     *
     * @return Session
     * @throws AuthenticationException
     */
    private function getSession(): Session
    {
        $session = Session::getInstance();

        if (is_null($session)) {
            throw new AuthenticationException(
                "Please the session configuration is not load"
            );
        }

        return $session;
    }

    /**
     * Check if user is guest
     *
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Make direct login
     *
     * @param  Authentication $user
     * @param  bool  $remember
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    public function login(Authentication $user, bool $remember = false): bool
    {
        $this->getSession()->add($this->session_key, $user);

        if ($remember) {
            $this->setRememberCookie($user);
        }

        return true;
    }

    /**
     * Make direct logout
     *
     * @return bool
     * @throws SessionException|AuthenticationException
     */
    public function logout(): bool
    {
        $user = $this->getSession()->get($this->session_key);

        if ($user instanceof Authentication) {
            $user->setRememberToken($this->generateRememberToken());
        }

        $this->clearRememberCookie();
        $this->getSession()->remove($this->session_key);

        return true;
    }

    /**
     * Get the user id
     *
     * @return mixed
     * @throws AuthenticationException|SessionException
     */
    public function id(): mixed
    {
        $user = $this->user();

        if (is_null($user)) {
            throw new AuthenticationException("No user is logged in for get his id");
        }

        return $user->getAuthenticateUserId();
    }

    /**
     * Check if user is authenticated
     *
     * @return ?Authentication
     * @throws AuthenticationException|SessionException
     */
    public function user(): ?Authentication
    {
        if (!$this->getSession()->exists($this->session_key)) {
            $this->attemptRememberLogin();
        }

        return $this->getSession()->get($this->session_key);
    }

    /**
     * Attempt to restore the session from a valid remember-me cookie.
     *
     * Never throws on malformed input: a bad cookie is simply cleared.
     *
     * @return bool
     * @throws AuthenticationException|SessionException
     */
    private function attemptRememberLogin(): bool
    {
        $cookie = Cookie::get($this->rememberCookieName());

        // Cookie::set() json-encodes its payload but Cookie::get() does not
        // json-decode it, so recover the original "<id>|<token>" string here.
        if (is_string($cookie)) {
            $decoded = json_decode($cookie, true);

            if (is_string($decoded)) {
                $cookie = $decoded;
            }
        }

        if (!is_string($cookie) || !str_contains($cookie, '|')) {
            if (!is_null($cookie)) {
                $this->clearRememberCookie();
            }
            return false;
        }

        [$id, $token] = explode('|', $cookie, 2);

        $user = $this->getUserById($id);

        if (is_null($user)) {
            $this->clearRememberCookie();
            return false;
        }

        $stored = $user->getRememberToken();

        if (is_null($stored) || !hash_equals($stored, $token)) {
            $this->clearRememberCookie();
            return false;
        }

        $this->getSession()->put($this->session_key, $user);

        return true;
    }

    /**
     * Generate a fresh remember token and persist it on the user, then
     * write the encrypted remember cookie.
     *
     * @param  Authentication $user
     * @return void
     */
    private function setRememberCookie(Authentication $user): void
    {
        $token = $this->generateRememberToken();
        $user->setRememberToken($token);

        Cookie::set(
            $this->rememberCookieName(),
            $user->getAuthenticateUserId() . '|' . $token,
            $this->rememberLifetime()
        );
    }

    /**
     * Remove the remember cookie.
     *
     * @return void
     */
    private function clearRememberCookie(): void
    {
        Cookie::remove($this->rememberCookieName());
    }

    /**
     * Get the remember cookie name for this guard.
     *
     * @return string
     */
    private function rememberCookieName(): string
    {
        return 'remember_' . $this->guard;
    }

    /**
     * Generate a cryptographically strong remember token.
     *
     * @return string
     */
    private function generateRememberToken(): string
    {
        return bin2hex(random_bytes(30));
    }

    /**
     * Get the configured remember-me cookie lifetime in seconds.
     *
     * @return int
     */
    private function rememberLifetime(): int
    {
        return (int) (config('auth.remember_lifetime') ?? 2592000);
    }
}
