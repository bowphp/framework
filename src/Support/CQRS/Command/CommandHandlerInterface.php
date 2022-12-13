<?php

namespace Bow\Support\CQRS\Command;

use Bow\Support\CQRS\Command\CommandInterface;

interface CommandHandlerInterface
{
    /**
     * Handle the command
     *
     * @param CommandInterface $command
     * @return mixed
     */
    public function process(CommandInterface $command): mixed;
}
