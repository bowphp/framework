<?php


class FTPServiceTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        \Bow\Storage\Storage::configure(require 'config/resource.php');
    }

    public function testUnsecureConnection()
    {
        Bow\Storage\Storage::pushService(['ftp' => \Bow\Storage\Service\FTPService::class]);
        /** @var \Bow\Storage\Service\FTPService $ftp_service_instance */
        $ftp_service_instance = \Bow\Storage\Storage::service('ftp');
        $this->assertInstanceOf(\Bow\Storage\Service\FTPService::class, $ftp_service_instance);
        $this->assertNotFalse($ftp_service_instance::getConnection());
    }
}