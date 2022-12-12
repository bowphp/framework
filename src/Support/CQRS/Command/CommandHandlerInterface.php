<?php

declare(strict_types=1);

namespace Bow\Support\CQRS\Command;

use Bow\Support\CQRS\Command\CommandInterface;

interface CommandHandlerInterface
{
    /**
     * Handle the command
     *
     * @param CommandInterface $command
     * @return void
     */
    public function process(CommandInterface $command);
}
