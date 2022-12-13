<?php

declare(strict_types=1);

namespace Bow\Support\CQRS\Command;

use Bow\Support\CQRS\Registration;
use Bow\Support\CQRS\Command\CommandInterface;

class CommandBus
{
    /**
     * Execute the passed command
     *
     * @param CommandInterface $command
     * @return mixed
     */
    public function execute(CommandInterface $command): mixed
    {
        $command_handler = Registration::getHandler($command);

        return $command_handler->process($command);
    }
}
