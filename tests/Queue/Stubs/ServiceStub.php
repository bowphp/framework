<?php

namespace Bow\Tests\Queue\Stubs;

class ServiceStub
{
    public function fire(): void
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/producer_service.txt', ServiceStub::class);
    }
}
