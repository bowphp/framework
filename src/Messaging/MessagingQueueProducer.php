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
     * @param Model $notifiable
     * @param Messaging $message
     */
    public function __construct(
        Model $notifiable,
        Messaging $message,
    ) {
        parent::__construct();

        $this->bags = [
            "message" => $message,
            "notifiable" => $notifiable,
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
        $message->process($this->bags['notifiable']);
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
