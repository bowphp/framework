<?php

namespace Bow\Tests\Filesystem;

use Bow\Storage\Service\DiskFilesystemService;
use Bow\Storage\Storage;
use Bow\Tests\Config\TestingConfiguration;

class DiskFilesystemTest extends \PHPUnit\Framework\TestCase
{
    private DiskFilesystemService $storage;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Storage::configure($config['resource']);
    }

    public static function tearDownAfterClass(): void
    {
        Storage::disk()->delete('simple_directory');
        Storage::disk()->delete('nested/directory');
    }

    public function setUp(): void
    {
        $this->storage = Storage::disk();
    }

    public function test_configuration()
    {
        $this->assertInstanceOf(DiskFilesystemService::class, $this->storage);
    }

    public function test_make_directory()
    {
        $first_result = $this->storage->makeDirectory('simple_directory');
        $secand_result = $this->storage->makeDirectory('nested/directory');

        $this->assertTrue($first_result);
        $this->assertTrue($secand_result);
    }
}
