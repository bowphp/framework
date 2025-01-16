<?php

namespace Bow\Tests\Auth;

use Bow\Auth\Auth;
use Bow\Security\Hash;
use Policier\Policier;
use Bow\Database\Database;
use Bow\Auth\Authentication;
use Bow\Auth\Guards\JwtGuard;
use Bow\Auth\Guards\SessionGuard;
use Bow\Auth\Guards\GuardContract;
use Bow\Tests\Auth\Stubs\UserModelStub;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Auth\Exception\AuthenticationException;

class AuthenticationTest extends \PHPUnit\Framework\TestCase
{
    protected static GuardContract $auth;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Auth::configure($config["auth"]);
        Policier::configure($config["policier"]);

        // Configuration database
        Database::configure($config["database"]);
        Database::statement("create table if not exists users (id int primary key auto_increment, name varchar(255), password varchar(255), username varchar(255))");
        Database::table('users')->insert([
            'name' => 'Franck',
            'password' => Hash::make("password"),
            'username' => 'papac'
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement("drop table if exists users;");
    }

    public function test_it_should_be_a_default_guard()
    {
        $config = TestingConfiguration::getConfig();
        $auth = Auth::getInstance();

        $this->assertEquals($auth->getName(), $config["auth"]["default"]);
        $this->assertEquals($auth->getName(), "web");
    }

    public function test_it_auth_instance()
    {
        $auth = Auth::getInstance();
        $this->assertInstanceOf(GuardContract::class, $auth);
    }

    public function test_it_should_be_session_guard_instance()
    {
        $auth = Auth::guard('web');
        $config = TestingConfiguration::getConfig();

        $this->assertInstanceOf(SessionGuard::class, $auth);
        $this->assertEquals($auth->getName(), $config["auth"]["default"]);
        $this->assertEquals($auth->getName(), "web");
    }

    public function test_it_should_be_session_jwt_instance()
    {
        $auth = Auth::guard('api');
        $config = TestingConfiguration::getConfig();

        $this->assertInstanceOf(JwtGuard::class, $auth);
        $this->assertNotEquals($auth->getName(), $config["auth"]["default"]);
        $this->assertEquals($auth->getName(), "api");
    }

    public function test_fail_get_user_id_with_jwt()
    {
        $this->expectException(AuthenticationException::class);
        $auth = Auth::guard('api');
        $auth->id();
    }

    public function test_fail_get_user_id_with_session()
    {
        $this->expectException(AuthenticationException::class);
        $auth = Auth::guard("web");
        $auth->id();
    }

    public function test_attempt_login_with_jwt_provider()
    {
        $auth = Auth::guard('api');

        $result = $auth->attempts([
            "username" => "papac",
            "password" => "password"
        ]);

        $this->assertTrue($result);

        $token = (string) $auth->getToken();
        $user = $auth->user();

        $this->assertInstanceOf(Authentication::class, $user);
        $this->assertTrue($auth->check());
        $this->assertEquals($auth->id(), $user->id);
        $this->assertMatchesRegularExpression("/^([a-zA-Z0-9_-]+\.){2}[a-zA-Z0-9_-]+$/", $token);
    }

    public function test_direct_login_with_jwt_provider()
    {
        $auth = Auth::guard('api');
        $auth->login(UserModelStub::first());

        $token = (string) $auth->getToken();
        $user = $auth->user();

        $this->assertTrue($auth->check());
        $this->assertInstanceOf(Authentication::class, $user);
        $this->assertEquals($auth->id(), $user->id);
        $this->assertMatchesRegularExpression("/^([a-zA-Z0-9_-]+\.){2}[a-zA-Z0-9_-]+$/", $token);
    }

    public function test_attempt_login_with_jwt_provider_fail()
    {
        $auth = Auth::guard('api');
        $result = $auth->attempts([
            "username" => "papac",
            "password" => "passwor"
        ]);

        $this->assertFalse($result);
        $this->assertFalse($auth->check());
        $this->assertNull($auth->getToken());
    }

    public function test_attempt_login_with_session_provider()
    {
        $this->expectException(AuthenticationException::class);

        $auth = Auth::guard('web');
        $auth->attempts([
            "username" => "papac",
            "password" => "password"
        ]);
    }

    public function test_direct_login_with_session_provider()
    {
        $this->expectException(AuthenticationException::class);
        $auth = Auth::guard('web');

        $auth->login(UserModelStub::first());
        $user = $auth->user();

        $this->assertInstanceOf(Authentication::class, $user);
        $this->assertEquals($user->name, 'Franck');
    }
}
