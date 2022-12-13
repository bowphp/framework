<?php

namespace Bow\Tests\Support\CQRS\Commands;

use Bow\Support\CQRS\Command\CommandInterface;

class CreatePetCommand implements CommandInterface
{
    public function __construct(public string $name)
    {
    }
}
