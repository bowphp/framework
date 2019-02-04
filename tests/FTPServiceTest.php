<?php


use Bow\Storage\Service\FTPService;
use Bow\Storage\Storage;

class FTPServiceTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass()
    {
        $env_file = dirname(__DIR__) . '/.env.json';

        if (file_exists($env_file) && !\Bow\Support\Env::isLoaded()) {
            \Bow\Support\Env::load($env_file);
        }

        Storage::configure(require 'config/resource.php');

        Storage::pushService(['ftp' => FTPService::class]);
    }

    public function testUnsecureConnection()
    {
        /** @var FTPService $ftp_service */
        $ftp_service = Storage::service('ftp');

        $this->assertInstanceOf(FTPService::class, $ftp_service);
        $this->assertInternalType('resource', $ftp_service->getConnection());
    }

    public function testHasCorrectRootFolder()
    {
        $config = require 'config/resource.php';
        $ftp_service = Storage::service('ftp');

        $this->assertEquals($ftp_service->getCurrentDirectory(), $config['services']['ftp']['root']);
    }

    public function testStore()
    {
        /** @var FTPService $ftp_service */
        $ftp_service = Storage::service('ftp');
        $file_content = 'Something very interesting';
        $file_name = 'test.txt';
        $result = $this->createFile($ftp_service, $file_name, $file_content);

        $this->assertInternalType('array', $result);
        $this->assertEquals($result['content'], $file_content);
        $this->assertEquals($result['path'], $file_name);
    }

    public function testGetInexistentFile()
    {
        $ftp_service = Storage::service('ftp');

        $this->setExpectedException(\Bow\Storage\Exception\ResourceException::class);
        $ftp_service->get('dummy.txt');
    }

    public function testGet()
    {
        /** @var FTPService $ftp_service */
        $ftp_service = Storage::service('ftp');
        $this->createFile($ftp_service, 'bow.txt', 'bow');

        $this->assertEquals($ftp_service->get('bow.txt'), 'bow');
    }

    public function testDelete()
    {
        $ftp_service = Storage::service('ftp');
        $file_name = 'delete.txt';
        $this->createFile($ftp_service, $file_name);
        $result = $ftp_service->delete($file_name);

        $this->assertTrue($result);
        $this->setExpectedException(\Bow\Storage\Exception\ResourceException::class);
        $ftp_service->get($file_name);
    }

    public function testRename()
    {
        $ftp_service = Storage::service('ftp');
        $this->createFile($ftp_service, 'file1.txt', 'from file 1');
        $result = $ftp_service->move('file1.txt', 'file2.txt');

        $this->assertTrue($result);
        $this->assertEquals($ftp_service->get('file2.txt'), 'from file 1');
    }

    public function testCopy()
    {
        /** @var FTPService $ftp_service */
        $ftp_service = Storage::service('ftp');
        $result = $ftp_service->copy('file-copy.txt', 'test.txt');

        $this->assertInternalType('array', $result);
        $this->assertEquals($ftp_service->get('test.txt'), $ftp_service->get('file-copy.txt'));
    }

    private function createFile(FTPService $ftpServiceInstance, $filename, $content = '')
    {
        $uploadedFile = $this->getMock(\Bow\Http\UploadFile::class, [], [[]]);
        $uploadedFile->method('getContent')->willReturn($content);
        $uploadedFile->method('getFilename')->willReturn($filename);
        
        return $ftpServiceInstance->store($uploadedFile, $filename);
    }
}
