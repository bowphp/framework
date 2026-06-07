<?php

namespace Bow\Tests\Auth;

use Bow\Auth\Auth;
use Bow\Database\Database;
use Bow\Security\Hash;
use Bow\Session\Session;
use Bow\Tests\Auth\Stubs\UserModelStub;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;

class RememberMeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Auth::configure($config["auth"]);
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
}
