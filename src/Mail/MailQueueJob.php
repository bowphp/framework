<?php

namespace Bow\Mail;

use Bow\Queue\QueueJob;
use Bow\View\View;
use Throwable;

class MailQueueJob extends QueueJob
{
    /**
     * The message bag
     *
     * @var array
     */
    private array $bags = [];

    /**
     * MailQueueJob constructor
     *
     * @param string  $view
     * @param array   $data
     * @param Envelop $message
     */
    public function __construct(
        string $view,
        array $data,
        Envelop $envelop
    ) {
        parent::__construct();

        $this->bags = [
            "view" => $view,
            "data" => $data,
            "envelop" => $envelop,
        ];
    }

    /**
     * Process mail
     *
     * @return void
     */
    public function process(): void
    {
        $envelop = $this->bags["envelop"];

        $envelop->setMessage(
            View::parse($this->bags["view"], $this->bags["data"])->getContent()
        );

        Mail::getInstance()->send($envelop);
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
