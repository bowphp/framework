<?php

namespace Bow\Tests\Hashing;

use Bow\Security\Crypto;
use Bow\Security\Hash;
use Bow\Tests\Config\TestingConfiguration;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        TestingConfiguration::getConfig();
    }

    public function test_should_decrypt_data()
    {
        Crypto::setkey(file_get_contents(__DIR__ . '/stubs/.key'), 'AES-256-CBC');

        $encrypted = Crypto::encrypt('bow');

        $this->assertEquals(Crypto::decrypt($encrypted), 'bow');
    }

    public function test_should_check_hash_value()
    {
        $hashed = Hash::create('bow');

        $this->assertTrue(Hash::check('bow', $hashed));
    }
}
