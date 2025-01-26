<?php

namespace Bow\Messaging;

use Bow\Database\Barry\Model;
use Bow\Queue\ProducerService;
use Throwable;

class MessagingQueueProducer extends ProducerService
{
    /**
     * The message bag
     *
     * @var array
     */
    private array $bags = [];

    /**
     * MessagingQueueProducer constructor
     *
     * @param Model $context
     * @param Messaging $message
     */
    public function __construct(
        Model     $context,
        Messaging $message,
    )
    {
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
     * @param Throwable $e
     * @return void
     */
    public function onException(Throwable $e): void
    {
        $this->deleteJob();
    }
}
