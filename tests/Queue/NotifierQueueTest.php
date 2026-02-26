<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\MailConfiguration;
use Bow\Notifier\Notifier;
use Bow\Notifier\NotifierQueueTask;
use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Connection as QueueConnection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Notifier\Stubs\MockChannelAdapter;
use Bow\Tests\Notifier\Stubs\TestNotifier;
use Bow\Tests\Notifier\Stubs\TestNotifiableModel;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;

class NotifierQueueTest extends TestCase
{
    private static QueueConnection $connection;

    public static function setUpBeforeClass(): void
    {
        // Suppress queue task logging during tests
        QueueAdapter::suppressLogging(true);

        TestingConfiguration::withConfigurations([
            CacheConfiguration::class,
            DatabaseConfiguration::class,
            QueueConfiguration::class,
            EnvConfiguration::class,
            LoggerConfiguration::class,
            MailConfiguration::class,
            ViewConfiguration::class,
        ]);

        $config = TestingConfiguration::getConfig();
        $config->boot();

        static::$connection = new QueueConnection($config["queue"]);

        // Mock external notification channels to avoid requiring real credentials
        Notifier::pushChannels([
            'mail' => MockChannelAdapter::class,
            'telegram' => MockChannelAdapter::class,
            'slack' => MockChannelAdapter::class,
            'sms' => MockChannelAdapter::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        MockChannelAdapter::reset();
    }

    private function createNotifierTask(): NotifierQueueTask
    {
        return new NotifierQueueTask(new TestNotifiableModel(), new TestNotifier());
    }

    public function test_can_send_message_synchronously(): void
    {
        $context = new TestNotifiableModel();
        $message = $this->getMockBuilder(TestNotifier::class)
            ->onlyMethods(['process'])
            ->getMock();

        $message->expects($this->once())
            ->method('process')
            ->with($context);

        $context->sendMessage($message);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_can_push_notifier_to_queue(string $connection): void
    {
        $task = $this->createNotifierTask();

        $this->assertInstanceOf(NotifierQueueTask::class, $task);

        try {
            $result = static::$connection->setConnection($connection)->getAdapter()->push($task);
            $this->assertTrue($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        }
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_can_push_notifier_with_queue_and_delay_options(string $connection): void
    {
        $task = $this->createNotifierTask();

        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $adapter->setQueue('notifications');
        $adapter->setSleep(3600);

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
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
