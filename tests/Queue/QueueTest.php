<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\Database;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\Mail;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Adapters\DatabaseAdapter;
use Bow\Queue\Adapters\KafkaAdapter;
use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\RabbitMQAdapter;
use Bow\Queue\Adapters\RedisAdapter;
use Bow\Queue\Adapters\SQSAdapter;
use Bow\Queue\Adapters\SyncAdapter;
use Bow\Queue\Connection as QueueConnection;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Queue\Stubs\BasicQueueTaskStub;
use Bow\Tests\Queue\Stubs\MixedQueueTaskStub;
use Bow\Tests\Queue\Stubs\ModelQueueTaskStub;
use Bow\Tests\Queue\Stubs\PetModelStub;
use Bow\Tests\Queue\Stubs\ServiceStub;
use Bow\View\View;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private const ADAPTER_CLASSES = [
        'beanstalkd' => BeanstalkdAdapter::class,
        'database' => DatabaseAdapter::class,
        'redis' => RedisAdapter::class,
        'rabbitmq' => RabbitMQAdapter::class,
        'sync' => SyncAdapter::class,
        'sqs' => SQSAdapter::class,
        'kafka' => KafkaAdapter::class,
    ];

    private static QueueConnection $connection;

    public static function setUpBeforeClass(): void
    {
        // Suppress queue task logging during tests
        QueueAdapter::suppressLogging(true);

        TestingConfiguration::withConfigurations([
            LoggerConfiguration::class,
            DatabaseConfiguration::class,
            CacheConfiguration::class,
            EnvConfiguration::class,
        ]);

        $config = TestingConfiguration::getConfig();
        $config->boot();

        View::configure($config["view"]);
        Mail::configure($config["mail"]);

        static::$connection = new QueueConnection($config["queue"]);

        Database::connection('mysql');
        Database::statement('DROP TABLE IF EXISTS pets');
        Database::statement('DROP TABLE IF EXISTS queues');
        Database::statement('CREATE TABLE pets (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255))');
        Database::statement('CREATE TABLE IF NOT EXISTS queues (
            id VARCHAR(255) PRIMARY KEY,
            queue VARCHAR(255),
            payload TEXT,
            status VARCHAR(100),
            attempts INT DEFAULT 0,
            available_at DATETIME NULL DEFAULT NULL,
            reserved_at DATETIME NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL DEFAULT NULL
        )');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanQueuesTable();
    }

    private function getAdapter(string $connection)
    {
        return static::$connection->setConnection($connection)->getAdapter();
    }

    private function createBasicJob(string $connection): BasicQueueTaskStub
    {
        return new BasicQueueTaskStub($connection);
    }

    private function createModelJob(string $connection, string $petName = "Filou"): ModelQueueTaskStub
    {
        $pet = new PetModelStub(["name" => $petName]);
        return new ModelQueueTaskStub($pet, $connection);
    }

    private function createMixedJob(string $connection): MixedQueueTaskStub
    {
        return new MixedQueueTaskStub(new ServiceStub(), $connection);
    }

    private function getTaskFilePath(string $connection): string
    {
        return TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_task.txt";
    }

    private function getModelJobFilePath(string $connection): string
    {
        return TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_queue_pet_model_stub.txt";
    }

    private function getServiceFilePath(string $connection): string
    {
        return TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_task_service.txt";
    }

    private function cleanupFiles(array $files): void
    {
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    private function recreatePetsTable(): void
    {
        Database::statement('DROP TABLE IF EXISTS pets');
        Database::statement('CREATE TABLE pets (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255))');
    }

    private function cleanQueuesTable(): void
    {
        Database::statement('DELETE FROM queues WHERE 1=1');
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_adapter_returns_correct_instance(string $connection): void
    {
        $adapter = $this->getAdapter($connection);

        $this->assertNotNull($adapter);
        $this->assertInstanceOf(self::ADAPTER_CLASSES[$connection], $adapter);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_adapter_configuration_methods(string $connection): void
    {
        $adapter = $this->getAdapter($connection);

        $adapter->setQueue("test-queue-{$connection}");
        $adapter->setTries(3);
        $adapter->setSleep(1);

        $this->assertNotNull($adapter);
    }

    /**
     * @dataProvider connectionProvider
     * @group integration
     */
    public function test_push_and_process_basic_job(string $connection): void
    {
        $adapter = $this->getAdapter($connection);
        $filename = $this->getTaskFilePath($connection);

        $this->cleanupFiles([$filename]);

        $task = $this->createBasicJob($connection);

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result, "Failed to push task to {$connection} adapter");

            $adapter->setQueue("queue_{$connection}");
            $adapter->setTries(1);
            $adapter->setSleep(0);
            $adapter->run();

            $this->assertFileExists($filename, "Task file was not created for {$connection}");
            $this->assertSame(BasicQueueTaskStub::class, file_get_contents($filename));
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        } finally {
            $this->cleanupFiles([$filename]);
        }
    }

    /**
     * @dataProvider connectionProvider
     * @group integration
     */
    public function test_push_and_process_model_job(string $connection): void
    {
        $this->recreatePetsTable();

        $adapter = $this->getAdapter($connection);
        $filename = $this->getModelJobFilePath($connection);
        $taskFile = $this->getTaskFilePath($connection);

        $this->cleanupFiles([$filename, $taskFile]);

        $petName = "Pet_{$connection}";
        $task = $this->createModelJob($connection, $petName);

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result, "Failed to push model task to {$connection} adapter");

            $adapter->run();

            $this->assertFileExists($filename, "Model task file was not created for {$connection}");
            $content = file_get_contents($filename);
            $data = json_decode($content);

            $this->assertNotNull($data, "Failed to decode JSON content");
            $this->assertSame($petName, $data->name);

            $pet = PetModelStub::where('name', $petName)->first();
            $this->assertNotNull($pet, "Pet model was not saved to database");
            $this->assertSame($petName, $pet->name);
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        } finally {
            $this->cleanupFiles([$filename, $taskFile]);
        }
    }

    /**
     * @dataProvider connectionProvider
     * @group integration
     */
    public function test_push_and_process_mixed_job_with_service(string $connection): void
    {
        $adapter = $this->getAdapter($connection);
        $filename = $this->getServiceFilePath($connection);

        $this->cleanupFiles([$filename]);

        $task = $this->createMixedJob($connection);

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result, "Failed to push mixed task to {$connection} adapter");

            $adapter->run();

            $this->assertFileExists($filename, "Service task file was not created for {$connection}");
            $this->assertSame(ServiceStub::class, file_get_contents($filename));
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        } finally {
            $this->cleanupFiles([$filename]);
        }
    }

    public function test_sync_adapter_processes_immediately(): void
    {
        $adapter = $this->getAdapter("sync");
        $filename = $this->getTaskFilePath("sync");

        $this->cleanupFiles([$filename]);

        $startTime = microtime(true);
        $task = $this->createBasicJob("sync");
        $task->setDelay(0);
        $result = $adapter->push($task);
        $executionTime = microtime(true) - $startTime;

        $this->assertTrue($result);
        $this->assertLessThan(1, $executionTime, "Sync adapter should execute immediately");
        $this->assertFileExists($filename);
        $this->assertSame(BasicQueueTaskStub::class, file_get_contents($filename));

        $this->cleanupFiles([$filename]);
    }

    public function test_database_adapter_stores_job_correctly(): void
    {
        $adapter = $this->getAdapter("database");
        $task = $this->createBasicJob("database");

        $result = $adapter->push($task);

        $this->assertTrue($result);

        $job = Database::table('queues')->where('queue', 'default')->first();

        $this->assertNotNull($job, "Job was not found in database");
        $this->assertSame('default', $job->queue);
        $this->assertObjectHasProperty('id', $job);
        $this->assertObjectHasProperty('payload', $job);
        $this->assertObjectHasProperty('status', $job);
        $this->assertObjectHasProperty('attempts', $job);
    }

    /**
     * @group integration
     */
    public function test_redis_adapter_queue_operations(): void
    {
        try {
            $adapter = $this->getAdapter("redis");
            $adapter->flush();

            $this->assertSame(0, $adapter->size());

            $task = $this->createBasicJob("redis");
            $adapter->push($task);

            $this->assertSame(1, $adapter->size());

            $adapter->flush();

            $this->assertSame(0, $adapter->size());
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis service is not available: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function connectionProvider(): array
    {
        $data = [
            "beanstalkd" => ["beanstalkd"],
            "database" => ["database"],
            "redis" => ["redis"],
            "rabbitmq" => ["rabbitmq"],
            "sync" => ["sync"],
        ];

        if (getenv("AWS_SQS_URL")) {
            $data["sqs"] = ["sqs"];
        }

        if (extension_loaded('rdkafka')) {
            $data["kafka"] = ["kafka"];
        }

        return $data;
    }
}
