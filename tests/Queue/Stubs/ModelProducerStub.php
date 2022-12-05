<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\ProducerService;
use Bow\Tests\Queue\Stubs\PetModelStub;

class ModelProducerStub extends ProducerService
{
    private PetModelStub $pet;

    public function __construct(PetModelStub $pet)
    {
        $this->pet = $pet;
    }

    public function process(): void
    {
        $this->pet->save();
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/queue_pet_model_stub.txt', $this->pet->toJson());
    }
}
