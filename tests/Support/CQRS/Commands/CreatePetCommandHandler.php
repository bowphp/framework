<?php

namespace Bow\Tests\Support\CQRS\Commands;

use Bow\Tests\Database\Stubs\PetModelStub;
use Bow\Support\CQRS\Command\CommandInterface;
use Bow\Support\CQRS\Command\CommandHandlerInterface;
use Bow\Tests\Support\CQRS\Commands\CreatePetCommand;

class CreatePetQueryHaCommand implements CommandHandlerInterface
{
    public function process(CommandInterface $command)
    {
        $pet = PetModelStub::create([
            "name" => $command->name,
        ]);

        return true;
    }
}
