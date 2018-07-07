<?php

class StorageTest extends PHPUnit\Framework\TestCase
{
    public function testConfiguration()
    {
        $storage = \Bow\Resource\Storage::configure(require "config/resource.php");

        $this->assertInstanceOf(\Bow\Resource\MountFilesystem::class, $storage);
    }
}
