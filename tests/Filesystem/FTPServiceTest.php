<?php

namespace Bow\Tests\Filesystem;

use Bow\Storage\Service\FTPService;
use Bow\Storage\Storage;
use Bow\Tests\Config\TestingConfiguration;

class FTPServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FTPService
     */
    private $ftp_service;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Storage::configure($config["resource"]);
    }

    protected function setUp(): void
    {
        $this->ftp_service = Storage::service('ftp');
    }

    protected function tearDown(): void
    {
        $this->ftp_service->setConnectionRoot();
    }

    public function test_the_connection()
    {
        $this->assertInstanceOf(FTPService::class, $this->ftp_service);
        $this->assertInstanceOf(\FTP\Connection::class, $this->ftp_service->getConnection());
    }

    public function test_ftp_base_root_should_equal_with_config_definition()
    {
        $config = TestingConfiguration::getConfig();
        $current_directory = $this->ftp_service->getCurrentDirectory();
        $root_folder = $config['resource']['services']['ftp']['root'];

        $this->assertEquals($current_directory, trim($root_folder, '/'));
    }

    public function test_create_new_file_into_ftp_server()
    {
        $file_content = 'Something very interesting';
        $file_name = 'test.txt';
        $result = $this->createFile($this->ftp_service, $file_name, $file_content);

        $this->assertIsArray($result);
        $this->assertEquals($result['content'], $file_content);
        $this->assertEquals($result['path'], $file_name);
    }

    public function test_file_should_not_be_existe()
    {
        $this->expectException(\Bow\Storage\Exception\ResourceException::class);
        $this->ftp_service->get('dummy.txt');
    }

    public function test_create_the_new_file_and_the_content()
    {
        $this->createFile($this->ftp_service, 'bow.txt', 'bow');

        $this->assertEquals($this->ftp_service->get('bow.txt'), 'bow');
    }

    public function test_delete_file_from_ftp_service()
    {
        $file_name = 'delete.txt';
        $this->createFile($this->ftp_service, $file_name);
        $result = $this->ftp_service->delete($file_name);

        $this->assertTrue($result);
        $this->expectException(\Bow\Storage\Exception\ResourceException::class);
        $this->ftp_service->get($file_name);
    }

    public function test_rename_file()
    {
        $this->createFile($this->ftp_service, 'file1.txt', 'from file 1');
        $result = $this->ftp_service->move('file1.txt', 'file2.txt');

        $this->assertTrue($result);
        $this->assertEquals($this->ftp_service->get('file2.txt'), 'from file 1');
    }

    public function test_copy_file_and_the_contents()
    {
        $result = $this->ftp_service->copy('file-copy.txt', 'test.txt');

        $this->assertTrue($result);
        $this->assertEquals($this->ftp_service->get('test.txt'), $this->ftp_service->get('file-copy.txt'));
    }

    public function test_make_directory()
    {
        $result = $this->ftp_service->makeDirectory('super/nested/dir');
        $result_1 = $this->ftp_service->makeDirectory('simple_dir');

        $this->assertTrue($result);
        $this->assertTrue($result_1);
    }

    public function test_get_directories_from_the_server()
    {
        $this->ftp_service->makeDirectory('for_test');
        $result = $this->ftp_service->directories();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_files_from_the_server()
    {
        $this->createFile($this->ftp_service, 'only_file.txt');
        $result = $this->ftp_service->files();
        $has_only_files = array_reduce($result, function ($acc, $next) {
            return $next['type'] === 'file';
        }, true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue($has_only_files);
    }

    public function test_check_if_is_directory()
    {
        $this->ftp_service->makeDirectory('mock_dir');
        $this->createFile($this->ftp_service, 'a_file.txt');

        $this->assertTrue($this->ftp_service->isDirectory('mock_dir'));
        $this->assertFalse($this->ftp_service->isDirectory('a_file.txt'));
    }

    public function test_check_if_is_file()
    {
        $this->ftp_service->makeDirectory('is_file');
        $this->createFile($this->ftp_service, 'is_file.txt');

        $this->assertTrue($this->ftp_service->isFile('is_file.txt'));
        $this->assertFalse($this->ftp_service->isFile('is_file'));
    }

    public function test_check_if_file_exists()
    {
        $this->createFile($this->ftp_service, 'exists.txt');

        $this->assertTrue($this->ftp_service->exists('exists.txt'));
        $this->assertFalse($this->ftp_service->exists('dont_exists.txt'));
    }

    public function test_append_content_into_file()
    {
        $this->createFile($this->ftp_service, 'append.txt', 'something');
        $this->ftp_service->append('append.txt', ' else');

        $this->assertRegExp('/something else/', $this->ftp_service->get('append.txt'));
    }

    public function test_prepend_content_into_file()
    {
        $this->createFile($this->ftp_service, 'prepend.txt', 'else');
        $this->ftp_service->prepend('prepend.txt', 'something ');

        $this->assertRegExp('/something else/', $this->ftp_service->get('prepend.txt'));
    }

    public function test_put_content_into_file()
    {
        $this->createFile($this->ftp_service, 'put.txt', 'something');
        $this->ftp_service->put('put.txt', ' else');

        $this->assertTrue(true);
    }

    private function createFile(FTPService $ftp_service, $filename, $content = '')
    {
        $uploadedFile = $this->createMock(\Bow\Http\UploadFile::class, [], [[]]);
        $uploadedFile->method('getContent')->willReturn($content);
        $uploadedFile->method('getFilename')->willReturn($filename);

        return $ftp_service->store($uploadedFile, $filename);
    }
}
