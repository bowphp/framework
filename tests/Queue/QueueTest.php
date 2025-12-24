<?php

namespace Bow\Tests\Queue;

use Bow\Cache\Adapters\RedisAdapter;
use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\Database;
use Bow\Database\DatabaseConfiguration;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Adapters\DatabaseAdapter;
use Bow\Queue\Adapters\SQSAdapter;
use Bow\Queue\Adapters\SyncAdapter;
use Bow\Queue\Connection as QueueConnection;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Queue\Stubs\BasicQueueJobStubs;
use Bow\Tests\Queue\Stubs\ModelJobStub;
use Bow\Tests\Queue\Stubs\PetModelStub;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
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
            id varchar(255) primary key,
            queue varchar(255),
            payload text,
            status varchar(100),
            attempts int,
            available_at datetime null default null,
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
    public function test_instance_of_adapter(string $connection): void
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $this->assertNotNull($adapter);

        if ($connection == "beanstalkd") {
            $this->assertInstanceOf(BeanstalkdAdapter::class, $adapter);
        } elseif ($connection == "sqs") {
            $this->assertInstanceOf(SQSAdapter::class, $adapter);
        } elseif ($connection == "redis") {
            $this->assertInstanceOf(RedisAdapter::class, $adapter);
        } elseif ($connection == "database") {
            $this->assertInstanceOf(DatabaseAdapter::class, $adapter);
        } elseif ($connection == "sync") {
            $this->assertInstanceOf(SyncAdapter::class, $adapter);
        }
    }

    /**
     * @dataProvider getConnection
     *
     * @param string $connection
     * @return void
     */
    public function test_push_service_adapter(string $connection): void
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $filename = TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer.txt";

        // Clean up before test
        @unlink($filename);

        $producer = new BasicQueueJobStubs($connection);
        $this->assertInstanceOf(BasicQueueJobStubs::class, $producer);

        $result = $adapter->push($producer);
        $this->assertTrue($result, "Failed to push producer to {$connection} adapter");

        $adapter->setQueue("queue_{$connection}");
        $adapter->setTries(3);
        $adapter->setSleep(5);
        $adapter->run();

        $this->assertFileExists($filename, "Producer file was not created for {$connection}");
        $this->assertEquals(BasicQueueJobStubs::class, file_get_contents($filename));

        @unlink($filename);
    }

    /**
     * @dataProvider getConnection
     * @param string $connection
     * @return void
     */
    public function test_push_service_adapter_with_model(string $connection): void
    {
        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $filename = TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_queue_pet_model_stub.txt";

        // Clean up before test
        @unlink($filename);
        @unlink(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer.txt");

        $pet = new PetModelStub(["name" => "Filou"]);
        $this->assertInstanceOf(PetModelStub::class, $pet);
        $this->assertEquals("Filou", $pet->name);

        $producer = new ModelJobStub($pet, $connection);
        $this->assertInstanceOf(ModelJobStub::class, $producer);

        $result = $adapter->push($producer);
        $this->assertTrue($result, "Failed to push model producer to {$connection} adapter");

        $adapter->run();

        $this->assertFileExists($filename, "Model producer file was not created for {$connection}");
        $content = file_get_contents($filename);
        $this->assertNotEmpty($content);

        $data = json_decode($content);
        $this->assertNotNull($data, "Failed to decode JSON content");
        $this->assertEquals("Filou", $data->name);

        $pet = PetModelStub::first();
        $this->assertNotNull($pet, "Pet model was not saved to database");
        $this->assertEquals("Filou", $pet->name);

        // Clean up after test
        @unlink($filename);
        @unlink(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer.txt");
    }

    /**
     * @test
     */
    public function test_can_set_queue_name(): void
    {
        $adapter = static::$connection->setConnection("sync")->getAdapter();
        $adapter->setQueue("custom-queue");

        $this->assertInstanceOf(SyncAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function test_can_set_retry_attempts(): void
    {
        $adapter = static::$connection->setConnection("sync")->getAdapter();
        $adapter->setTries(5);

        $this->assertInstanceOf(SyncAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function test_can_set_sleep_delay(): void
    {
        $adapter = static::$connection->setConnection("sync")->getAdapter();
        $adapter->setSleep(10);

        $this->assertInstanceOf(SyncAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function test_sync_adapter_processes_immediately(): void
    {
        $adapter = static::$connection->setConnection("sync")->getAdapter();
        $filename = TESTING_RESOURCE_BASE_DIRECTORY . "/sync_producer.txt";

        @unlink($filename);

        $producer = new BasicQueueJobStubs("sync");
        $result = $adapter->push($producer);

        $this->assertTrue($result);
        $this->assertFileExists($filename);
        $this->assertEquals(BasicQueueJobStubs::class, file_get_contents($filename));

        @unlink($filename);
    }

    /**
     * @test
     */
    public function test_database_adapter_stores_job_in_database(): void
    {
        $adapter = static::$connection->setConnection("database")->getAdapter();
        $this->assertInstanceOf(DatabaseAdapter::class, $adapter);

        $producer = new BasicQueueJobStubs("database");
        $result = $adapter->push($producer);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function test_can_switch_between_connections(): void
    {
        $syncAdapter = static::$connection->setConnection("sync")->getAdapter();
        $this->assertInstanceOf(SyncAdapter::class, $syncAdapter);

        $databaseAdapter = static::$connection->setConnection("database")->getAdapter();
        $this->assertInstanceOf(DatabaseAdapter::class, $databaseAdapter);

        $beanstalkdAdapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $this->assertInstanceOf(BeanstalkdAdapter::class, $beanstalkdAdapter);
    }

    /**
     * Get the connection data
     *
     * @return array
     */
    public function getConnection(): array
    {
        $data = [
            ["beanstalkd"],
            ["database"],
            ["sync"],
        ];

        if (getenv("AWS_SQS_URL")) {
            $data[] = ["sqs"];
        }

        return $data;
    }
}
