<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\ProducerService;
use Bow\Tests\Queue\Stubs\ServiceStub;

class MixedProducerStub extends ProducerService
{
    private ServiceStub $service;

    public function __construct(ServiceStub $service)
    {
        $this->service = $service;
    }

    public function process(): void
    {
        $this->service->fire();
    }
}
