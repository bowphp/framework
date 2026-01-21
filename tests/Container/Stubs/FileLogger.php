<?php

namespace Bow\Tests\Container\Stubs;

class FileLogger implements LoggerInterface
{
    /**
     * @var array
     */
    private array $messages = [];

    /**
     * @inheritDoc
     */
    public function log(string $message): void
    {
        $this->messages[] = '[FILE] ' . $message;
    }

    /**
     * @inheritDoc
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
