<?php

namespace Bow\Tests\Queue;

use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Connection as QueueConnection;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Queue\Stubs\ProducerStubs;

class QueueTest extends \PHPUnit\Framework\TestCase
{
    private static $connection;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        @unlink(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt');
        static::$connection = new QueueConnection($config["queue"]);
    }

    public function test_instance_of_adapter()
    {
        $this->assertInstanceOf(BeanstalkdAdapter::class, static::$connection->getAdapter());
    }

    public function test_push_service_adapter()
    {
        $adapter = static::$connection->getAdapter();
        $adapter->push(new ProducerStubs("running"));
        $adapter->run();

        $this->assertTrue(file_exists(TESTING_RESOURCE_BASE_DIRECTORY.'/producer.txt'));
        $this->assertEquals(file_get_contents(TESTING_RESOURCE_BASE_DIRECTORY.'/producer.txt'), 'running');
    }
}
