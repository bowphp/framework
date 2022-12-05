<?php

namespace Bow\Tests\Queue;

use Bow\Database\Database;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Queue\Stubs\PetModelStub;
use Bow\Queue\Connection as QueueConnection;
use Bow\Tests\Queue\Stubs\ModelProducerStub;
use Bow\Tests\Queue\Stubs\BasicProducerStubs;

class QueueTest extends \PHPUnit\Framework\TestCase
{
    private static $connection;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        @unlink(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt');
        static::$connection = new QueueConnection($config["queue"]);

        Database::connection('mysql');
        Database::statement('drop table if exists pets');
        Database::statement('create table pets (id int primary key auto_increment, name varchar(255))');
    }

    public function test_instance_of_adapter()
    {
        $this->assertInstanceOf(BeanstalkdAdapter::class, static::$connection->getAdapter());
    }

    public function test_push_service_adapter()
    {
        $adapter = static::$connection->getAdapter();
        $adapter->push(new BasicProducerStubs("running"));
        $adapter->run();

        $this->assertTrue(file_exists(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt'));
        $this->assertEquals(file_get_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt'), 'running');
    }

    public function test_push_service_adapter_with_model()
    {
        $adapter = static::$connection->getAdapter();
        $pet = new PetModelStub(["name" => "Filou"]);
        $producer = new ModelProducerStub($pet);

        $adapter->push($producer);
        $adapter->run();

        $this->assertTrue(file_exists(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt'));
        $this->assertEquals(file_get_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt'), 'running');

        $pet = PetModelStub::first();
        $this->assertNotNull($pet);
    }
}
