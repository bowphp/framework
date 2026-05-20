<?php

namespace Bow\Tests\Container\Stubs;

interface LoggerInterface
{
    /**
     * Log a message
     *
     * @param string $message
     * @return void
     */
    public function log(string $message): void;

    /**
     * Get all logged messages
     *
     * @return array
     */
    public function getMessages(): array;
}
