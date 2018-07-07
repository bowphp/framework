<?php

use Bow\Security\Hash;
use Bow\Security\Crypto;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    public function testHashValue()
    {
        return Hash::make('bow');
    }

    public function testEncryptValue()
    {
        Crypto::setKey(file_get_contents(__DIR__.'/config/.key'), 'AES-256-CBC');

        return Crypto::encrypt('bow');
    }

    /**
     * @depends testEncryptValue
     * @param $data
     */
    public function testDecrypt($data)
    {
        Crypto::setkey(file_get_contents(__DIR__.'/config/.key'), 'AES-256-CBC');

        $this->assertEquals(Crypto::decrypt($data), 'bow');
    }

    /**
     * @depends testHashValue
     * @param $data
     */
    public function testHash($data)
    {
        $this->assertTrue(Hash::check('bow', $data));
    }
}
