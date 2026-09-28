<?php

namespace Bow\Tests\Queue\Stubs;

use Bow\Queue\QueueTask;
use RuntimeException;
use Throwable;

class FailingQueueTaskStub extends QueueTask
{
    public function __construct(
        private bool $dropAfterFailure = false
    ) {
    }

    public function process(): void
    {
        throw new RuntimeException('task blew up');
    }

    public function onException(Throwable $e)
    {
        if ($this->dropAfterFailure) {
            $this->deleteTask();
        }
    }
}
