<?php

class StorageTest extends PHPUnit\Framework\TestCase
{
    public function testConfiguration()
    {
        $storage = \Bow\Storage\Storage::configure(require "config/resource.php");

        $this->assertInstanceOf(\Bow\Storage\MountFilesystem::class, $storage);
    }
}
