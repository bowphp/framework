<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\ProducerService;
use Bow\Tests\Queue\Stubs\PetModelStub;

class ModelProducerStub extends ProducerService
{
    public function __construct(
        private PetModelStub $pet,
        private string $connection
    ) {
        $this->pet = $pet;
        $this->connection = $connection;
    }

    public function process(): void
    {
        $this->pet->save();

        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . "/{$this->connection}_queue_pet_model_stub.txt", $this->pet->toJson());

        $this->deleteJob();
    }
}
