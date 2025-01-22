<?php

namespace Bow\Mail;

use Bow\Queue\ProducerService;
use Bow\View\View;
use Throwable;

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
        parent::__construct();

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
        $message = $this->bags["message"];

        $message->setMessage(
            View::parse($this->bags["view"], $this->bags["data"])->getContent()
        );

        Mail::getInstance()->send($message);
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
