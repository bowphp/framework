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
     * NotifierQueueJob constructor
     *
     * @param Model     $context
     * @param Notifier $notifier
     */
    public function __construct(
        Model $context,
        Notifier $notifier,
    ) {
        parent::__construct();

        $this->bags = [
            "notifier" => $notifier,
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
        $notifier = $this->bags['notifier'];
        $notifier->process($this->bags['context']);
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
