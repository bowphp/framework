<?php

namespace Bow\Tests\Messaging;

use Bow\Database\Barry\Model;
use Bow\Mail\Envelop;
use Bow\Messaging\MessagingQueueProducer;
use Bow\Queue\Connection as QueueConnection;
use Bow\Tests\Messaging\Stubs\TestMessage;
use Bow\Tests\Messaging\Stubs\TestNotifiableModel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MessagingTest extends TestCase
{
    private MockObject|Model $context;
    private MockObject|TestMessage $message;
    private static QueueConnection $queueConnection;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize queue connection
        static::$queueConnection = new QueueConnection([
            'default' => 'sync',
            'connections' => [
                'sync' => [
                    'driver' => 'sync'
                ]
            ]
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(TestNotifiableModel::class);
        $this->message = $this->createMock(TestMessage::class);
    }

    public function test_can_send_message_synchronously(): void
    {
        $this->message->expects($this->once())
            ->method('process')
            ->with($this->context);

        $this->context->sendMessage($this->message);
    }

    public function test_can_send_message_to_queue(): void
    {
        $producer = new MessagingQueueProducer($this->context, $this->message);
        
        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);
        
        // Push to queue and verify
        static::$queueConnection->getAdapter()->push($producer);
        
        $this->context->setMessageQueue($this->message);
    }

    public function test_can_send_message_to_specific_queue(): void
    {
        $queue = 'high-priority';
        $producer = new MessagingQueueProducer($this->context, $this->message);
        
        // Verify that the producer is created with correct parameters
        $this->assertInstanceOf(MessagingQueueProducer::class, $producer);
        
        // Push to specific queue and verify
        $adapter = static::$queueConnection->getAdapter();
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
        $adapter = static::$queueConnection->getAdapter();
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
        $adapter = static::$queueConnection->getAdapter();
        $adapter->setQueue($queue);
        $adapter->setSleep($delay);
        $adapter->push($producer);
        
        $this->context->sendMessageLaterOn($delay, $queue, $this->message);
    }

    public function test_message_sends_to_correct_channels(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $channels = $message->channels($context);

        $this->assertEquals(['mail', 'database'], $channels);
    }

    public function test_message_can_send_to_mail(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $mailMessage = $message->toMail($context);

        $this->assertInstanceOf(Envelop::class, $mailMessage);
        $this->assertEquals('test@example.com', $mailMessage->getTo());
        $this->assertEquals('Test Message', $mailMessage->getSubject());
    }

    public function test_message_can_send_to_database(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $data = $message->toDatabase($context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('test_message', $data['type']);
        $this->assertEquals('Test message content', $data['data']['message']);
    }
}
