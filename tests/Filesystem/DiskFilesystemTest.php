<?php

namespace Bow\Tests\Filesystem;

use Bow\Http\UploadedFile;
use Bow\Storage\Service\DiskFilesystemService;
use Bow\Storage\Storage;
use Bow\Tests\Config\TestingConfiguration;

class DiskFilesystemTest extends \PHPUnit\Framework\TestCase
{
    private DiskFilesystemService $storage;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Storage::configure($config['storage']);
    }

    public static function tearDownAfterClass(): void
    {
        Storage::disk()->delete('testing_directory');
        Storage::disk()->delete('nested/directory');
        Storage::disk()->delete('nested/testing/directory');
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
        $first_result = $this->storage->makeDirectory('testing_directory');
        $second_result = $this->storage->makeDirectory('nested/directory');
        $thrid_result = $this->storage->makeDirectory('nested/testing/directory');

        $this->assertTrue($first_result);
        $this->assertTrue($second_result);
        $this->assertTrue($thrid_result);
    }

    public function test_fail_make_directory()
    {
        $first_result = $this->storage->makeDirectory('testing_directory');
        $second_result = $this->storage->makeDirectory('nested/directory');
        $thrid_result = $this->storage->makeDirectory('nested/testing/directory');

        $this->assertFalse($first_result);
        $this->assertFalse($second_result);
        $this->assertFalse($thrid_result);
    }

    public function test_delete_directory()
    {
        $first_result = $this->storage->makeDirectory('testing_directory');
        $second_result = $this->storage->makeDirectory('nested/directory');
        $thrid_result = $this->storage->makeDirectory('nested/testing/directory');

        $this->assertFalse($first_result);
        $this->assertFalse($second_result);
        $this->assertFalse($thrid_result);
    }

    public function test_get_path()
    {
        $path = sprintf("%s/%s", $this->storage->getBaseDirectory(), "filename.txt");
        $result = $this->storage->path("filename.txt");

        $this->assertEquals($result, $path);
    }

    public function test_get_path_by_passed_the_right_path()
    {
        $path = sprintf("%s/%s", $this->storage->getBaseDirectory(), "filename.txt");

        $this->assertEquals($this->storage->path($path), $path);
    }

    public function test_is_directory()
    {
        $this->storage->makeDirectory("is_directory");

        $this->assertTrue($this->storage->isDirectory("is_directory"));
        $this->assertFalse($this->storage->isDirectory("is_not_a_directory"));
    }

    public function test_is_file()
    {
        file_put_contents($this->storage->getBaseDirectory() . "/tmp_file.txt", "some content");

        $this->assertTrue($this->storage->isFile("tmp_file.txt"));
        $this->assertFalse($this->storage->isFile("is_not_a_right_file.txt"));
    }

    public function test_file_extension()
    {
        $filename = $this->storage->getBaseDirectory() . "/tmp_file.txt";
        file_put_contents($filename, "some content");

        $this->assertEquals($this->storage->extension($filename), "txt");
        $this->assertNull($this->storage->extension("is_not_a_right_file.txt"));
    }

    public function test_put_as_sucess()
    {
        $result = $this->storage->put("put_file_name.txt", "some content");

        $this->assertTrue($result);
    }

    public function test_store()
    {
        $uploadedFile = $this->getUploadedFileMock();

        $filename = sprintf("%s.txt", md5(time()));
        $uploadedFile->method("getHashName")->willReturn($filename);

        $result = $this->storage->store($uploadedFile);
        $this->assertTrue($result);
    }

    public function getUploadedFileMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        $uploadedFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uploadedFile->method("getContent")->willReturn("some content");

        return $uploadedFile;
    }

    public function test_store_on_custom_store()
    {
        $uploadedFile = $this->getUploadedFileMock();

        $filename = sprintf("%s.txt", md5(time()));
        $uploadedFile->method("getHashName")->willReturn($filename);

        $result = $this->storage->store($uploadedFile, "stores");
        $this->assertTrue($result);
    }

    public function test_store_with_location_by_filename_setting()
    {
        $uploadedFile = $this->getUploadedFileMock();

        $filename = "stub_store_filename.txt";
        $result = $this->storage->store($uploadedFile, "stores", [
            "as" => $filename
        ]);

        $this->assertTrue($result);
        $this->assertTrue($this->storage->isFile("stores/$filename"));
    }

    public function test_store_with_location_as_null_and_filename_as_null()
    {
        $uploadedFile = $this->getUploadedFileMock();

        $filename = sprintf("%s.txt", md5(time()));
        $uploadedFile->method("getHashName")->willReturn($filename);

        $result = $this->storage->store($uploadedFile);

        $this->assertTrue($result);
        $this->assertTrue($this->storage->isFile($filename));
    }

    public function test_store_with_location_as_array_with_as_filename_key()
    {
        $uploadedFile = $this->getUploadedFileMock();

        $result = $this->storage->store($uploadedFile, [
            "as" => "stub_store_filename.txt"
        ]);

        $this->assertTrue($result);
        $this->assertTrue($this->storage->isFile("stores/stub_store_filename.txt"));
    }
}
