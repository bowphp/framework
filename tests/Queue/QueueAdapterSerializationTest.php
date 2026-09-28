<?php

declare(strict_types=1);

namespace Bow\Tests\Queue;

use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\QueueTask;
use Bow\Security\Crypto;
use Bow\Tests\Queue\Stubs\BasicQueueTaskStub;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueueAdapterSerializationTest extends TestCase
{
    private QueueAdapter $adapter;

    protected function setUp(): void
    {
        Crypto::setKey(base64_encode(str_repeat('b', 32)));

        $this->adapter = new class extends QueueAdapter {
            public function configure(array $config): QueueAdapter
            {
                return $this;
            }

            public function push(QueueTask $task): bool
            {
                return true;
            }
        };
    }

    public function test_it_round_trips_a_task(): void
    {
        $payload = $this->adapter->serializeProducer(new BasicQueueTaskStub("round-trip"));

        $this->assertInstanceOf(BasicQueueTaskStub::class, $this->adapter->unserializeProducer($payload));
    }

    public function test_it_rejects_a_payload_whose_task_class_is_not_loadable(): void
    {
        // A task enqueued by a producer running code the worker does not have:
        // the class was renamed, removed, or lives in another service. unserialize()
        // yields a __PHP_Incomplete_Class, which must not escape as a TypeError.
        $payload = Crypto::encrypt('O:35:"App\Tasks\SyncWhatsAppTemplatesTask":0:{}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('App\Tasks\SyncWhatsAppTemplatesTask');

        $this->adapter->unserializeProducer($payload);
    }

    public function test_it_rejects_a_payload_that_does_not_hold_a_task(): void
    {
        $payload = Crypto::encrypt(serialize(['id' => 1]));

        $this->expectException(RuntimeException::class);

        $this->adapter->unserializeProducer($payload);
    }
}
