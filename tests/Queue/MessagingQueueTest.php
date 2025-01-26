<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\Barry\Model;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\MailConfiguration;
use Bow\Messaging\MessagingQueueProducer;
use Bow\Queue\Connection as QueueConnection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Messaging\Stubs\TestMessage;
use Bow\Tests\Messaging\Stubs\TestNotifiableModel;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessagingQueueTest extends TestCase
{
    private static QueueConnection $connection;
    private MockObject|Model $context;
    private MockObject|TestMessage $message;

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

        $this->message->expects($this->once())
            ->method('process')
            ->with($context);

        $context->sendMessage($this->message);
    }

    public function test_can_send_message_to_queue(): void
    {
        $producer = new MessagingQueueProducer($this->context, $this->message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);

        // Push to queue and verify
        static::$connection->setConnection("beanstalkd")->getAdapter()->push($producer);

        $this->context->setMessageQueue($this->message);
    }

    public function test_can_send_message_to_specific_queue(): void
    {
        $queue = 'high-priority';
        $producer = new MessagingQueueProducer($this->context, $this->message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);

        // Push to specific queue and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setQueue($queue);
        $adapter->push($producer);

        $this->context->sendMessageQueueOn($queue, $this->message);
    }

    public function test_can_send_message_with_delay(): void
    {
        $delay = 3600;
        $producer = new MessagingQueueProducer($this->context, $this->message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);

        // Push to queue and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setSleep($delay);
        $adapter->push($producer);

        $this->context->sendMessageLater($delay, $this->message);
    }

    public function test_can_send_message_with_delay_on_specific_queue(): void
    {
        $delay = 3600;
        $queue = 'delayed-notifications';
        $producer = new MessagingQueueProducer($this->context, $this->message);

        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);

        // Push to specific queue with delay and verify
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setQueue($queue);
        $adapter->setSleep($delay);
        $adapter->push($producer);

        $this->context->sendMessageLaterOn($delay, $queue, $this->message);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(TestNotifiableModel::class);
        $this->message = $this->createMock(TestMessage::class);
    }
}
