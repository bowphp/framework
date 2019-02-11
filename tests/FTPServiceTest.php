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

    public function testUnsecureConnection()
    {
        $this->assertInstanceOf(FTPService::class, $this->ftp_service);
        $this->assertInternalType('resource', $this->ftp_service->getConnection());
    }

    public function testHasCorrectRootFolder()
    {
        $config = require 'config/resource.php';

        $this->assertEquals($this->ftp_service->getCurrentDirectory(), $config['services']['ftp']['root']);
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

    }

    private function createFile(FTPService $ftp_service, $filename, $content = '')
    {
        $uploadedFile = $this->getMock(\Bow\Http\UploadFile::class, [], [[]]);
        $uploadedFile->method('getContent')->willReturn($content);
        $uploadedFile->method('getFilename')->willReturn($filename);
        
        return $ftp_service->store($uploadedFile, $filename);
    }
}
