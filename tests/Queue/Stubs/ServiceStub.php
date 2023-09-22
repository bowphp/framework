<?php

namespace Bow\Tests\Queue\Stubs;

class ServiceStub
{
    /**
     * The fire method
     *
     * @param string $connection
     * @return void
     */
    public function fire(string $connection): void
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . "/{$connection}_producer_service.txt", ServiceStub::class);
    }
}
