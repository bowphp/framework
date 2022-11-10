<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\ProducerService;

class BasicProducerStubs extends ProducerService
{
    private ?string $name = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function process(): void
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/producer.txt', $this->name);
    }
}
