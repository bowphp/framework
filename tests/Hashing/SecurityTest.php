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

    public function test_decrypt_fails_closed_on_non_authenticated_input()
    {
        $key = file_get_contents(__DIR__ . '/stubs/.key');
        Crypto::setkey($key, 'AES-256-CBC');
        Crypto::allowLegacy(false);

        // A legacy (static-IV, unauthenticated) ciphertext with no BOW2: header.
        $cipher = 'AES-256-CBC';
        $iv = substr(sha1($key), 0, (int) openssl_cipher_iv_length($cipher));
        $legacy = openssl_encrypt('secret', $cipher, $key, 0, $iv);

        $this->assertFalse(Crypto::decrypt($legacy));
        $this->assertFalse(Crypto::decrypt('garbage'));
    }

    public function test_decrypt_reads_legacy_only_when_opted_in()
    {
        $key = file_get_contents(__DIR__ . '/stubs/.key');
        Crypto::setkey($key, 'AES-256-CBC');

        $cipher = 'AES-256-CBC';
        $iv = substr(sha1($key), 0, (int) openssl_cipher_iv_length($cipher));
        $legacy = openssl_encrypt('secret', $cipher, $key, 0, $iv);

        Crypto::allowLegacy(true);
        $this->assertEquals('secret', Crypto::decrypt($legacy));

        Crypto::allowLegacy(false);
        $this->assertFalse(Crypto::decrypt($legacy));
    }
}
