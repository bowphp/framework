<?php

namespace Bow\Tests\Queue;

use Bow\Cache\Adapter\RedisAdapter;
use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\Database;
use Bow\Database\DatabaseConfiguration;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Adapters\DatabaseAdapter;
use Bow\Queue\Adapters\SQSAdapter;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Queue\Stubs\PetModelStub;
use Bow\Queue\Connection as QueueConnection;
use Bow\Testing\KernelTesting;
use Bow\Tests\Queue\Stubs\ModelProducerStub;
use Bow\Tests\Queue\Stubs\BasicProducerStubs;

class QueueTest extends \PHPUnit\Framework\TestCase
{
    private static $connection;

    public static function setUpBeforeClass(): void
    {
        TestingConfiguration::withConfigurations([
            LoggerConfiguration::class,
            DatabaseConfiguration::class,
            CacheConfiguration::class,
            EnvConfiguration::class,
        ]);

        $config = TestingConfiguration::getConfig();
        $config->boot();

        static::$connection = new QueueConnection($config["queue"]);

        Database::connection('mysql');
        Database::statement('drop table if exists pets');
        Database::statement('create table pets (id int primary key auto_increment, name varchar(255))');
        Database::statement('create table if not exists queues (
            id int primary key auto_increment,
            queue varchar(255),
            payload text,
            status varchar(100),
            attempts int,
            reserved_at datetime null default null,
            created_at datetime
        )');
    }

    /**
     * @dataProvider getConnection
     *
     * @param string $connection
     * @return void
     */
    public function test_instance_of_adapter($connection)
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();

        if ($connection == "beanstalkd") {
            $this->assertInstanceOf(BeanstalkdAdapter::class, $adapter);
        } elseif ($connection == "sqs") {
            $this->assertInstanceOf(SQSAdapter::class, $adapter);
        } elseif ($connection == "redis") {
            $this->assertInstanceOf(RedisAdapter::class, $adapter);
        } elseif ($connection == "database") {
            $this->assertInstanceOf(DatabaseAdapter::class, $adapter);
        }
    }

    /**
     * @dataProvider getConnection
     *
     * @param string $connection
     * @return void
     */
    public function test_push_service_adapter($connection)
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $filename = TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer.txt";

        $adapter->push(new BasicProducerStubs($connection));
        $adapter->run();

        $this->assertTrue(file_exists($filename));
        $this->assertEquals(file_get_contents($filename), BasicProducerStubs::class);

        @unlink($filename);
    }

    /**
     * @dataProvider getConnection
     * @param string $connection
     * @return void
     */
    public function test_push_service_adapter_with_model($connection)
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $pet = new PetModelStub(["name" => "Filou"]);
        $producer = new ModelProducerStub($pet, $connection);

        $adapter->push($producer);
        $adapter->run();

        $this->assertTrue(file_exists(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_queue_pet_model_stub.txt"));
        $content = file_get_contents(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_queue_pet_model_stub.txt");
        $data = json_decode($content);
        $this->assertEquals($data->name, "Filou");

        $pet = PetModelStub::first();
        $this->assertNotNull($pet);

        @unlink(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer.txt");
    }

    /**
     * Get the connection data
     *
     * @return array
     */
    public function getConnection(): array
    {
        return [
            ["beanstalkd"],
            ["sqs"],
            ["database"],
            // ["redis"],
            // ["rabbitmq"],
        ];
    }
}
