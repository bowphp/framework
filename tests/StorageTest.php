<?php

class StorageTest extends PHPUnit\Framework\TestCase
{
    public function testConfiguration()
    {
        $storage = \Bow\Storage\Storage::configure(require "config/resource.php");

        $this->assertInstanceOf(\Bow\Storage\MountFilesystem::class, $storage);
    }

    public function testMakeDirectory()
    {
        $storage = \Bow\Storage\Storage::configure(require 'config/resource.php');
        $result = $storage->makeDirectory('simple_dir');
        $result2 = $storage->makeDirectory('nested/dir');

        $this->assertTrue($result);
        $this->assertTrue($result2);
    }
}
