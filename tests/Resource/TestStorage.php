<?php

use Bow\Support\Resource\Storage;

class TextStorage extends \PHPUnit_Framework_TestCase
{
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

}