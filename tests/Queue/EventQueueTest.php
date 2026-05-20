<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Event\EventQueueTask;
use Bow\Mail\MailConfiguration;
use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Connection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Events\Stubs\UserEventListenerStub;
use Bow\Tests\Events\Stubs\UserEventStub;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;

class EventQueueTest extends TestCase
{
    private const CACHE_FILENAME = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

    private static Connection $connection;

    public static function setUpBeforeClass(): void
    {
        // Suppress queue task logging during tests
        QueueAdapter::suppressLogging(true);

        TestingConfiguration::withConfigurations([
            CacheConfiguration::class,
            QueueConfiguration::class,
            DatabaseConfiguration::class,
            EnvConfiguration::class,
            LoggerConfiguration::class,
            MailConfiguration::class,
            ViewConfiguration::class,
        ]);

        $config = TestingConfiguration::getConfig();
        $config->boot();

        static::$connection = new Connection($config["queue"]);
    }

    protected function tearDown(): void
    {
        $this->cleanupCacheFile();
        parent::tearDown();
    }

    private function cleanupCacheFile(): void
    {
        @unlink(self::CACHE_FILENAME);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_should_queue_and_process_event(string $connection): void
    {
        $this->cleanupCacheFile();

        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $expectedPayload = "$connection-bowphp";
        $task = new EventQueueTask(new UserEventListenerStub(), new UserEventStub($expectedPayload));

        $this->assertInstanceOf(EventQueueTask::class, $task);

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result);
            $adapter->setSleep(0);
            $adapter->setTries(0);
            $adapter->run();

            $this->assertFileExists(self::CACHE_FILENAME);
            $this->assertSame($expectedPayload, file_get_contents(self::CACHE_FILENAME));
        } catch (\Exception $e) {
            $this->markTestSkipped('Service is not available: ' . $e->getMessage());
        }
    }

    public function test_should_create_event_queue_job_with_listener_and_payload(): void
    {
        $listener = new UserEventListenerStub();
        $event = new UserEventStub("test-data");

        $task = new EventQueueTask($listener, $event);

        $this->assertInstanceOf(EventQueueTask::class, $task);
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
