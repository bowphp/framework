<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\ProducerService;
use Bow\Tests\Queue\Stubs\ServiceStub;

class MixedProducerStub extends ProducerService
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
