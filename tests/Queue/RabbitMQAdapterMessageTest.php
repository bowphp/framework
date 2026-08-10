<?php

declare(strict_types=1);

namespace Bow\Tests\Queue;

use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\RabbitMQAdapter;
use Bow\Security\Crypto;
use Bow\Tests\Queue\Stubs\BasicQueueTaskStub;
use Bow\Tests\Queue\Stubs\FailingQueueTaskStub;
use PHPUnit\Framework\TestCase;

class RabbitMQAdapterMessageTest extends TestCase
{
    private RabbitMQAdapter $adapter;

    public static function setUpBeforeClass(): void
    {
        QueueAdapter::suppressLogging(true);
    }

    public static function tearDownAfterClass(): void
    {
        QueueAdapter::suppressLogging(false);
    }

    protected function setUp(): void
    {
        Crypto::setKey(base64_encode(str_repeat('c', 32)));

        // configure() is skipped on purpose: consuming a single message needs no
        // broker connection, only the message itself.
        $this->adapter = new class extends RabbitMQAdapter {
            public function consume(object $message): void
            {
                $this->processMessage($message);
            }

            // The failure path throttles with a real sleep; tests must not pay it.
            public function sleep(int $seconds): void
            {
            }
        };
    }

    public function test_it_acknowledges_a_processed_message(): void
    {
        $message = $this->message(
            $this->adapter->serializeProducer(new BasicQueueTaskStub("rabbitmq_ack"))
        );

        $this->adapter->consume($message);

        $this->assertSame(1, $message->acked);
        $this->assertSame(0, $message->nacked);
    }

    public function test_it_nacks_a_payload_whose_task_class_is_not_loadable(): void
    {
        // The unserialize failure must not escape the consumer callback: an
        // unacked, unnacked message is redelivered forever and crash-loops the
        // worker on the same poison payload.
        $message = $this->message(Crypto::encrypt('O:35:"App\Tasks\SyncWhatsAppTemplatesTask":0:{}'));

        $this->adapter->consume($message);

        $this->assertSame(0, $message->acked);
        $this->assertSame(1, $message->nacked);
        $this->assertFalse($message->requeued);
    }

    public function test_it_nacks_a_tampered_payload(): void
    {
        $message = $this->message("not-a-valid-payload");

        $this->adapter->consume($message);

        $this->assertSame(0, $message->acked);
        $this->assertSame(1, $message->nacked);
        $this->assertFalse($message->requeued);
    }

    public function test_it_requeues_a_task_that_failed_but_may_be_retried(): void
    {
        // A transient failure (broker blip, timeout) must go back on the queue
        // instead of being discarded on the first throw.
        $message = $this->message($this->adapter->serializeProducer(
            $this->failingTask()
        ));

        $this->adapter->consume($message);

        $this->assertSame(0, $message->acked);
        $this->assertSame(1, $message->nacked);
        $this->assertTrue($message->requeued);
    }

    public function test_it_drops_a_failed_task_that_asked_to_be_deleted(): void
    {
        $message = $this->message($this->adapter->serializeProducer(
            $this->failingTask(dropAfterFailure: true)
        ));

        $this->adapter->consume($message);

        $this->assertSame(0, $message->acked);
        $this->assertSame(1, $message->nacked);
        $this->assertFalse($message->requeued);
    }

    /**
     * Build a failing task the way push() delivers one: with an id already set.
     */
    private function failingTask(bool $dropAfterFailure = false): FailingQueueTaskStub
    {
        $task = new FailingQueueTaskStub($dropAfterFailure);
        $task->setId("rabbitmq-failing-task");

        return $task;
    }

    private function message(string $body): object
    {
        return new class ($body) {
            public int $acked = 0;
            public int $nacked = 0;
            public bool $requeued = false;

            public function __construct(public string $body)
            {
            }

            public function ack(): void
            {
                $this->acked++;
            }

            public function nack(bool $requeue = false, bool $multiple = false): void
            {
                $this->nacked++;
                $this->requeued = $requeue;
            }
        };
    }
}
