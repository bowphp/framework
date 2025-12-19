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
}
