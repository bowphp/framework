<?php


use Bow\Storage\Storage;

class FTPServiceTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        Storage::configure(require 'config/resource.php');
        Storage::pushService(['ftp' => \Bow\Storage\Service\FTPService::class]);
    }

    public function testUnsecureConnection()
    {
        /** @var \Bow\Storage\Service\FTPService $ftp_service_instance */
        $ftp_service_instance = Storage::service('ftp');
        $this->assertInstanceOf(\Bow\Storage\Service\FTPService::class, $ftp_service_instance);
        $this->assertNotFalse($ftp_service_instance::getConnection());
    }

    public function testHasCorrectRootFolder()
    {
        $config = require 'config/resource.php';
        $ftp_service_instance = Storage::service('ftp');
        $this->assertEquals($ftp_service_instance->getCurrentDir(), $config['ftp']['root']);
    }

    public function testStore()
    {
        /** @var \Bow\Storage\Service\FTPService $ftp_service */
        $ftp_service = Storage::service('ftp');
        $file_content = 'Something very interesting';
        $file_name = 'test.txt';
        $uploadedFile = $this->getMock(\Bow\Http\UploadFile::class, [], [[]]);
        $uploadedFile->method('getContent')->willReturn($file_content);
        $uploadedFile->method('getFilename')->willReturn($file_name);
        $result = $ftp_service->store($uploadedFile, $uploadedFile->getFilename());
        $this->assertInternalType('array', $result);
        $this->assertEquals($result['content'], $file_content);
    }
}
