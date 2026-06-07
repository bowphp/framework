<?php

namespace Bow\Tests\Auth;

use Bow\Auth\Auth;
use Bow\Database\Database;
use Bow\Security\Crypto;
use Bow\Security\Hash;
use Bow\Session\Session;
use Bow\Tests\Auth\Stubs\UserModelStub;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;
use Policier\Policier;

/**
 * Bow's native session relies on session_set_save_handler()/session_start(),
 * which both fail once PHPUnit's default runner has emitted output
 * (headers_sent() === true). Running the whole class in a dedicated process
 * lets the session boot cleanly before any output is produced.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RememberMeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Crypto::setKey($config['security']['key'], $config['security']['cipher']);

        Auth::configure($config["auth"]);
        Policier::configure($config["policier"]);
        Database::configure($config["database"]);
        Session::configure((array) $config["session"]);

        $driver = $config["database"]["default"];
        $idColumn = $driver === 'pgsql'
            ? 'id SERIAL PRIMARY KEY'
            : ($driver === 'mysql'
                ? 'id INTEGER PRIMARY KEY AUTO_INCREMENT'
                : 'id INTEGER PRIMARY KEY AUTOINCREMENT');

        Database::statement("DROP TABLE IF EXISTS users");
        Database::statement(
            "CREATE TABLE IF NOT EXISTS users ("
            . "$idColumn, "
            . "name VARCHAR(255), password VARCHAR(255), "
            . "username VARCHAR(255), remember_token VARCHAR(100) NULL)"
        );
        Database::table('users')->insert([
            'name' => 'Franck',
            'password' => Hash::make('password'),
            'username' => 'papac',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement("DROP TABLE IF EXISTS users");
    }

    protected function setUp(): void
    {
        ob_start();
        $_COOKIE = [];
        Database::table('users')->where('username', 'papac')->update(['remember_token' => null]);
    }

    protected function tearDown(): void
    {
        ob_get_clean();
    }

    public function test_remember_token_name_defaults_to_remember_token()
    {
        $user = UserModelStub::first();
        $this->assertSame('remember_token', $user->getRememberTokenName());
    }

    public function test_set_and_get_remember_token_persists()
    {
        $user = UserModelStub::first();
        $this->assertNull($user->getRememberToken());

        $user->setRememberToken('abc123');

        $this->assertSame('abc123', $user->getRememberToken());
        $fresh = UserModelStub::first();
        $this->assertSame('abc123', $fresh->getRememberToken());
    }

    public function test_remember_token_can_be_cleared_to_null()
    {
        $user = UserModelStub::first();
        $user->setRememberToken('to-be-cleared');
        $this->assertSame('to-be-cleared', UserModelStub::first()->getRememberToken());

        $user->setRememberToken(null);

        $this->assertNull($user->getRememberToken());
        $this->assertNull(UserModelStub::first()->getRememberToken());
    }

    public function test_get_user_by_id_returns_the_user()
    {
        $auth = Auth::guard('web');
        $expected = UserModelStub::first();

        $method = new \ReflectionMethod($auth, 'getUserById');
        $method->setAccessible(true);
        $user = $method->invoke($auth, $expected->getAuthenticateUserId());

        $this->assertNotNull($user);
        $this->assertSame(
            $expected->getAuthenticateUserId(),
            $user->getAuthenticateUserId()
        );
    }

    public function test_get_user_by_id_returns_null_for_unknown_id()
    {
        $auth = Auth::guard('web');

        $method = new \ReflectionMethod($auth, 'getUserById');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($auth, 999999));
    }

    public function test_jwt_guard_accepts_but_ignores_remember_flag()
    {
        $auth = Auth::guard('api');

        $result = $auth->attempts([
            'username' => 'papac',
            'password' => 'password',
        ], true);

        $this->assertTrue($result);
        // The JWT guard must not set any cookie when remember=true.
        $this->assertEmpty($_COOKIE);
    }

    private function rememberCookieValue(int $id, string $token): string
    {
        // Mirror Cookie::set()'s encoding (json_encode then Crypto::encrypt) so
        // tests exercise the same payload shape attemptRememberLogin() will read.
        return Crypto::encrypt(json_encode($id . '|' . $token));
    }

    public function test_attempts_with_remember_persists_token()
    {
        $auth = Auth::guard('web');

        $result = $auth->attempts([
            'username' => 'papac',
            'password' => 'password',
        ], true);

        $this->assertTrue($result);
        $this->assertNotNull(UserModelStub::first()->getRememberToken());
    }

    public function test_attempts_without_remember_leaves_token_null()
    {
        $auth = Auth::guard('web');

        $auth->attempts([
            'username' => 'papac',
            'password' => 'password',
        ], false);

        $this->assertNull(UserModelStub::first()->getRememberToken());
    }

    public function test_check_restores_session_from_valid_remember_cookie()
    {
        $auth = Auth::guard('web');
        $user = UserModelStub::first();
        $user->setRememberToken('valid-token-123');

        Session::getInstance()->remove('_auth_web');
        $_COOKIE['remember_web'] = $this->rememberCookieValue(
            (int) $user->getAuthenticateUserId(),
            'valid-token-123'
        );

        $this->assertTrue($auth->check());
        $this->assertSame('papac', $auth->user()->username);
    }

    public function test_check_rejects_tampered_token_and_clears_cookie()
    {
        $auth = Auth::guard('web');
        $user = UserModelStub::first();
        $user->setRememberToken('the-real-token');

        Session::getInstance()->remove('_auth_web');
        $_COOKIE['remember_web'] = $this->rememberCookieValue(
            (int) $user->getAuthenticateUserId(),
            'WRONG-token'
        );

        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey('remember_web', $_COOKIE);
    }

    public function test_check_rejects_unknown_user_and_clears_cookie()
    {
        $auth = Auth::guard('web');

        Session::getInstance()->remove('_auth_web');
        $_COOKIE['remember_web'] = $this->rememberCookieValue(999999, 'whatever');

        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey('remember_web', $_COOKIE);
    }

    public function test_check_rejects_malformed_cookie_without_delimiter()
    {
        $auth = Auth::guard('web');

        Session::getInstance()->remove('_auth_web');
        // A well-encrypted cookie whose payload has no "<id>|<token>" delimiter.
        $_COOKIE['remember_web'] = Crypto::encrypt(json_encode('garbage-no-pipe'));

        $this->assertFalse($auth->check());
        $this->assertArrayNotHasKey('remember_web', $_COOKIE);
    }

    public function test_logout_regenerates_token_and_removes_cookie()
    {
        $auth = Auth::guard('web');
        $user = UserModelStub::first();
        $user->setRememberToken('token-before');

        Session::getInstance()->remove('_auth_web');
        $_COOKIE['remember_web'] = $this->rememberCookieValue(
            (int) $user->getAuthenticateUserId(),
            'token-before'
        );
        $this->assertTrue($auth->check());

        $this->assertTrue($auth->logout());

        $this->assertArrayNotHasKey('remember_web', $_COOKIE);
        $this->assertNotSame('token-before', UserModelStub::first()->getRememberToken());
    }
}
