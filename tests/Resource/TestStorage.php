<?php

$config = [
    "hostname" => "192.168.1.101",
    "username" => "franck",
    "password" => "papac1010",
    "port"     => 21,
    "root" => 'test',
    "tls" => false, // A `true` pour activer une connection sécurisé.
    "timeout" => 50 // Temps d'attente de connection
];

use Bow\Support\Resource\Storage;
use Bow\Support\Resource\Ftp\FTP;

class TextStorage extends \PHPUnit_Framework_TestCase
{
    /**
     * @var
     */
    public $ftp;
    public function testPut()
    {
        $this->assertEquals(11, Storage::put(dirname(__DIR__) . '/data/file.txt', 'hello world'));
    }

    public function testGet()
    {
        $this->assertNotEquals(null, Storage::get(dirname(__DIR__) . '/data/file.txt'));
    }

    public function testMakeDirectory()
    {
        $this->assertEquals(true, Storage::makeDirectory(dirname(__DIR__) . '/data/test'));
    }

    public function testDeleteDirectory()
    {
        $this->assertEquals(true, Storage::delete(dirname(__DIR__) . '/data/test'));
    }

    public function testAppend()
    {
        $this->assertEquals(4, Storage::append(dirname(__DIR__) . '/data/file.txt', 'data'));
    }

    public function testPrepend()
    {
        $this->assertEquals(true, Storage::prepend(dirname(__DIR__) . '/data/file.txt', 'dakia'));
    }

    public function testFile()
    {
        $r = Storage::files(dirname(__DIR__) . '/data');
        $this->assertEquals(true, in_array(dirname(__DIR__) . '/data/file.txt', $r));
    }

    public function testDelete()
    {
        $this->assertEquals(true, Storage::delete(dirname(__DIR__) . '/data/file.txt'));
    }

    public function testFtpConnection()
    {
        global $config;
        $this->assertEquals(true, Storage::ftp($config) instanceof FTP);
    }

    public function testFTPGetContent()
    {
        $this->assertEquals("test\n", Storage::ftp()->get('data.txt'));
    }

    public function testFTPGetToFile()
    {
        $this->assertEquals(true, Storage::ftp()->get('data.txt', dirname(__DIR__) . '/data/ftp.txt'));
    }

    public function testFTPNlist()
    {
        $lists = Storage::ftp()->listDirectory('.');
        $this->assertEquals(true, is_array($lists));
    }

    public function testFTPRow()
    {
        $lists = Storage::ftp()->rawlist('.');
        $this->assertEquals(true, is_array($lists));
    }

    public function testFTPSize()
    {
        $size = Storage::ftp()->size('data.txt');
        $this->assertEquals(true, is_int($size));
    }

    public function testFTPIsFile()
    {
        $this->assertEquals(true, Storage::ftp()->isFile('data.txt'));
    }
}