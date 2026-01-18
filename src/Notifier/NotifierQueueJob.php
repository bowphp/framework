<?php

namespace Bow\Notifier;

use Bow\Database\Barry\Model;
use Bow\Queue\QueueJob;
use Throwable;

class NotifierQueueJob extends QueueJob
{
    /**
     * The message bag
     *
     * @var array
     */
    private array $bags = [];

    /**
     * NotifierQueueMessage constructor
     *
     * @param Model     $context
     * @param Notifier $message
     */
    public function __construct(
        Model $context,
        Notifier $message,
    ) {
        parent::__construct();

        $this->bags = [
            "message" => $message,
            "context" => $context,
        ];
    }

    /**
     * Process mail
     *
     * @return void
     */
    public function process(): void
    {
        $message = $this->bags['message'];
        $message->process($this->bags['context']);
    }

    /**
     * Send the processing exception
     *
     * @param  Throwable $e
     * @return void
     */
    public function onException(Throwable $e): void
    {
        $this->deleteJob();
    }
}
