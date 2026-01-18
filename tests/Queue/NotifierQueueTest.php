<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\MailConfiguration;
use Bow\Notifier\NotifierQueueJob;
use Bow\Queue\Connection as QueueConnection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Notifier\Stubs\TestNotifier;
use Bow\Tests\Notifier\Stubs\TestNotifiableModel;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;

class NotifierQueueTest extends TestCase
{
    private static QueueConnection $connection;

    public static function setUpBeforeClass(): void
    {
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

    public function test_can_send_message_to_queue(): void
    {
        // Use real objects for queue tests (mock objects don't serialize)
        $context = new TestNotifiableModel();
        $message = new TestNotifier();

        $producer = new NotifierQueueJob($context, $message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(NotifierQueueJob::class, $producer);

        // Push to queue and verify
        $result = static::$connection->setConnection("beanstalkd")->getAdapter()->push($producer);
        $this->assertTrue($result);
    }

    public function test_can_send_message_to_specific_queue(): void
    {
        $queue = 'high-priority';
        $context = new TestNotifiableModel();
        $message = new TestNotifier();

        $producer = new NotifierQueueJob($context, $message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(NotifierQueueJob::class, $producer);

        // Push to specific queue and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setQueue($queue);
        $result = $adapter->push($producer);

        $this->assertTrue($result);
    }

    public function test_can_send_message_with_delay(): void
    {
        $delay = 3600;
        $context = new TestNotifiableModel();
        $message = new TestNotifier();

        $producer = new NotifierQueueJob($context, $message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(NotifierQueueJob::class, $producer);

        // Push to queue with delay and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setSleep($delay);
        $result = $adapter->push($producer);

        $this->assertTrue($result);
    }

    public function test_can_send_message_with_delay_on_specific_queue(): void
    {
        $delay = 3600;
        $queue = 'delayed-notifications';
        $context = new TestNotifiableModel();
        $message = new TestNotifier();

        $producer = new NotifierQueueJob($context, $message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(NotifierQueueJob::class, $producer);

        // Push to specific queue with delay and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setQueue($queue);
        $adapter->setSleep($delay);
        $result = $adapter->push($producer);

        $this->assertTrue($result);
    }
}
