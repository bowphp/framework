<?php

use Bow\Security\Security;
use Bow\Security\Hash;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    public function testHashValue()
    {
        return Hash::make('bow');
    }

    public function testEncryptValue()
    {
        Security::setkey(file_get_contents(__DIR__.'/config/.key'), 'AES-256-CBC');
        return  Security::encrypt('bow');
    }

    /**
     * @depends testEncryptValue
     * @param $data
     */
    public function testDecrypt($data)
    {
        Security::setkey(file_get_contents(__DIR__.'/config/.key'), 'AES-256-CBC');
        $this->assertEquals(Security::decrypt($data), 'bow');
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