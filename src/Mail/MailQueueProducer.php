<?php

namespace Bow\Mail;

use Bow\Mail\Mail;
use Bow\Mail\Message;
use Bow\Queue\ProducerService;

class MailQueueProducer extends ProducerService
{
    /**
     * The message bag
     *
     * @var array
     */
    private array $bags = [];

    /**
     * MailQueueProducer constructor
     *
     * @param string $view
     * @param array $data
     * @param Message $message
     */
    public function __construct(
        string $view,
        array $data,
        Message $message
    ) {
        $this->bags = [
            "view" => $view,
            "data" => $data,
            "message" => $message,
        ];
    }

    /**
     * Process mail
     *
     * @return void
     */
    public function process(): void
    {
        Mail::getInstance()->send($this->bags["message"]);
    }
}
