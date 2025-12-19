<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\QueueJob;

class MixedProducerStub extends QueueJob
{
    public function __construct(
        private ServiceStub $service,
        private string $connection
    ) {
    }

    public function process(): void
    {
        $this->service->fire($this->connection);
    }
}
