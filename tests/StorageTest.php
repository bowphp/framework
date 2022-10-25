<?php declare(strict_types=1);

use Bow\Storage\Storage;
use Bow\Storage\Service\DiskFilesystemService;

class StorageTest extends PHPUnit\Framework\TestCase
{
    private DiskFilesystemService $storage;

    public static function setUpBeforeClass(): void
    {
        $env_file = dirname(__DIR__) . '/.env.json';

        if (file_exists($env_file) && !\Bow\Support\Env::isLoaded()) {
            \Bow\Support\Env::load($env_file);
        }

        Storage::configure(require 'config/resource.php');
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
        $first_result = $this->storage->makeDirectory('simple_dir');
        $secand_result = $this->storage->makeDirectory('nested/dir');

        $this->assertTrue($first_result);
        $this->assertTrue($secand_result);
    }
}
