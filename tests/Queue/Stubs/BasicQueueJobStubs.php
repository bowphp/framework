<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\QueueMessage;

class BasicQueueMessageStubs extends QueueMessage
{
    public function __construct(
        private string $connection
    ) {
    }

    public function process(): void
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . "/{$this->connection}_producer.txt", BasicQueueMessageStubs::class);
    }
}
