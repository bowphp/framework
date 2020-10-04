<?php


use Bow\Storage\Service\FTPService;
use Bow\Storage\Storage;

class FTPServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FTPService
     */
    private $ftp_service;

    public static function setUpBeforeClass()
    {
        $env_file = dirname(__DIR__) . '/.env.json';

        if (file_exists($env_file) && !\Bow\Support\Env::isLoaded()) {
            \Bow\Support\Env::load($env_file);
        }

        Storage::configure(require 'config/resource.php');

        Storage::pushService(['ftp' => FTPService::class]);
    }

    protected function setUp()
    {
        $this->ftp_service = Storage::service('ftp');
    }

    protected function tearDown()
    {
        $this->ftp_service->setConnectionRoot();
    }

    public function testUnsecuredConnection()
    {
        $this->assertInstanceOf(FTPService::class, $this->ftp_service);
        $this->assertInternalType('resource', $this->ftp_service->getConnection());
    }

    public function testHasCorrectRootFolder()
    {
        $config = require 'config/resource.php';
        $current_directory = $this->ftp_service->getCurrentDirectory();
        $root_folder = $config['services']['ftp']['root'];

        $this->assertEquals($current_directory, trim($root_folder, '/'));
    }

    public function testStore()
    {
        $file_content = 'Something very interesting';
        $file_name = 'test.txt';
        $result = $this->createFile($this->ftp_service, $file_name, $file_content);

        $this->assertInternalType('array', $result);
        $this->assertEquals($result['content'], $file_content);
        $this->assertEquals($result['path'], $file_name);
    }

    public function testGetInexistentFile()
    {
        $this->setExpectedException(\Bow\Storage\Exception\ResourceException::class);
        $this->ftp_service->get('dummy.txt');
    }

    public function testGet()
    {
        $this->createFile($this->ftp_service, 'bow.txt', 'bow');

        $this->assertEquals($this->ftp_service->get('bow.txt'), 'bow');
    }

    public function testDelete()
    {
        $file_name = 'delete.txt';
        $this->createFile($this->ftp_service, $file_name);
        $result = $this->ftp_service->delete($file_name);

        $this->assertTrue($result);
        $this->setExpectedException(\Bow\Storage\Exception\ResourceException::class);
        $this->ftp_service->get($file_name);
    }

    public function testRename()
    {
        $this->createFile($this->ftp_service, 'file1.txt', 'from file 1');
        $result = $this->ftp_service->move('file1.txt', 'file2.txt');

        $this->assertTrue($result);
        $this->assertEquals($this->ftp_service->get('file2.txt'), 'from file 1');
    }

    public function testCopy()
    {
        $result = $this->ftp_service->copy('file-copy.txt', 'test.txt');

        $this->assertInternalType('array', $result);
        $this->assertEquals($this->ftp_service->get('test.txt'), $this->ftp_service->get('file-copy.txt'));
    }

    public function testMakeDirectory()
    {
        $result = $this->ftp_service->makeDirectory('super/nested/dir');
        $result_1 = $this->ftp_service->makeDirectory('simple_dir');

        $this->assertTrue($result);
        $this->assertTrue($result_1);
    }

    public function testDirectories()
    {
        $this->ftp_service->makeDirectory('for_test');
        $result = $this->ftp_service->directories();

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    public function testFiles()
    {
        $this->createFile($this->ftp_service, 'only_file.txt');
        $result = $this->ftp_service->files();
        $has_only_files = array_reduce($result, function ($acc, $next) {
            return $next['type'] === 'file';
        }, true);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertTrue($has_only_files);
    }

    public function testIsDirectory()
    {
        $this->ftp_service->makeDirectory('mock_dir');
        $this->createFile($this->ftp_service, 'a_file.txt');

        $this->assertTrue($this->ftp_service->isDirectory('mock_dir'));
        $this->assertFalse($this->ftp_service->isDirectory('a_file.txt'));
    }

    public function testIsFile()
    {
        $this->ftp_service->makeDirectory('is_file');
        $this->createFile($this->ftp_service, 'is_file.txt');

        $this->assertTrue($this->ftp_service->isFile('is_file.txt'));
        $this->assertFalse($this->ftp_service->isFile('is_file'));
    }

    public function testExists()
    {
        $this->createFile($this->ftp_service, 'exists.txt');

        $this->assertTrue($this->ftp_service->exists('exists.txt'));
        $this->assertFalse($this->ftp_service->exists('dont_exists.txt'));
    }

    public function testAppend()
    {
        $this->createFile($this->ftp_service, 'append.txt', 'something');
        $this->ftp_service->append('append.txt', ' else');

        $this->assertRegExp('/something else/', $this->ftp_service->get('append.txt'));
    }

    public function testPrepend()
    {
        $this->createFile($this->ftp_service, 'prepend.txt', 'else');
        $this->ftp_service->prepend('prepend.txt', 'something ');

        $this->assertRegExp('/something else/', $this->ftp_service->get('prepend.txt'));
    }

    public function testPut()
    {
        $this->createFile($this->ftp_service, 'put.txt', 'something');
        $this->ftp_service->put('put.txt', ' else');

        $this->assertTrue(true);
    }

    private function createFile(FTPService $ftp_service, $filename, $content = '')
    {
        $uploadedFile = $this->getMock(\Bow\Http\UploadFile::class, [], [[]]);
        $uploadedFile->method('getContent')->willReturn($content);
        $uploadedFile->method('getFilename')->willReturn($filename);

        return $ftp_service->store($uploadedFile, $filename);
    }
}
