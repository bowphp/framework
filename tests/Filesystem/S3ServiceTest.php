<?php

namespace Bow\Tests\Filesystem;

use Bow\Storage\Service\S3Service;
use Bow\Storage\Storage;
use Bow\Tests\Config\TestingConfiguration;

class S3ServiceTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Storage::configure($config["storage"]);
    }

    public function test_instance_of_s3_service()
    {
        $s3 = Storage::service('s3');

        $this->assertInstanceOf(S3Service::class, $s3);
    }

    public function test_put_file()
    {
        $s3 = Storage::service('s3');

        $result = $s3->put("my-file.txt", "Content", ['visibility' => 'public']);

        $this->assertTrue($result);
    }

    public function test_get_file()
    {
        $s3 = Storage::service('s3');

        $content = $s3->get("my-file.txt");

        $this->assertEquals('Content', $content);
    }

    public function test_copy_file()
    {
        $s3 = Storage::service('s3');

        $result = $s3->copy("my-file.txt", "the-copy-file.txt");
        $first_file_content = $s3->get("my-file.txt");
        $second_file_content = $s3->get("the-copy-file.txt");

        $this->assertTrue($result);
        $this->assertEquals($second_file_content, $first_file_content);
    }

    public function test_delete_file()
    {
        $s3 = Storage::service('s3');
        $s3->put("delete-me.txt", "To be deleted");
        $result = $s3->delete("delete-me.txt");
        $this->assertTrue($result);
        $this->assertFalse($s3->exists("delete-me.txt"));
    }

    public function test_exists_file()
    {
        $s3 = Storage::service('s3');
        $s3->put("exists.txt", "Exists");
        $this->assertTrue($s3->exists("exists.txt"));
        $s3->delete("exists.txt");
        $this->assertFalse($s3->exists("exists.txt"));
    }

    public function test_list_files()
    {
        $s3 = Storage::service('s3');
        $s3->put("file1.txt", "A");
        $s3->put("file2.txt", "B");

        $files = $s3->files('/');
        $this->assertContains("file1.txt", $files);
        $this->assertContains("file2.txt", $files);
    }

    public function test_get_nonexistent_file_returns_null_or_false()
    {
        $s3 = Storage::service('s3');
        $result = $s3->get("not-found.txt");
        $this->assertTrue($result === null || $result === false);
    }

    public function test_store_uploaded_file()
    {
        $s3 = Storage::service('s3');
        $fileMock = $this->createMock(\Bow\Http\UploadedFile::class);
        $fileMock->method('getHashName')->willReturn('uploaded.txt');
        $fileMock->method('getContent')->willReturn('Uploaded content');
        $location = $s3->store($fileMock);
        $this->assertIsString($location);
        $this->assertNotEmpty($location);
        $this->assertEquals('Uploaded content', $s3->get('uploaded.txt'));
    }

    public function test_append_and_prepend_file()
    {
        $s3 = Storage::service('s3');
        $s3->put('append.txt', 'First');
        $s3->append('append.txt', 'Second');
        $content = $s3->get('append.txt');
        $this->assertStringContainsString('First', $content);
        $this->assertStringContainsString('Second', $content);

        $s3->prepend('append.txt', 'Zero');
        $content = $s3->get('append.txt');
        $this->assertStringContainsString('Zero', $content);
    }

    public function test_move_file()
    {
        $s3 = Storage::service('s3');
        $s3->put('move-source.txt', 'MoveMe');
        $result = $s3->move('move-source.txt', 'move-target.txt');
        $this->assertTrue($result);
        $this->assertEquals('MoveMe', $s3->get('move-target.txt'));
        $this->assertNull($s3->get('move-source.txt'));
    }

    public function test_make_directory_and_directories()
    {
        $s3 = Storage::service('s3');
        $result = $s3->makeDirectory('new-bucket');
        $this->assertTrue($result);
        $dirs = $s3->directories('new-bucket');
        $this->assertIsArray($dirs);
        $this->assertContains('new-bucket', $dirs);
    }

    public function test_path_returns_url()
    {
        $s3 = Storage::service('s3');
        $s3->put('url.txt', 'URLContent');
        $url = $s3->path('url.txt');
        $this->assertIsString($url);
        $this->assertStringContainsString('url.txt', $url);
    }

    public function test_is_file_and_is_directory()
    {
        $s3 = Storage::service('s3');
        $s3->put('isfile.txt', 'FileContent');
        $this->assertTrue($s3->isFile('isfile.txt'));
        $s3->makeDirectory('isdir-bucket');
        $this->assertTrue($s3->isDirectory('isdir-bucket'));
    }
}
