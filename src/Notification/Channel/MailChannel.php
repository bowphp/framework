<?php

namespace Bow\Notification\Channel;

use Bow\Mail\Mail;
use Bow\Mail\Message;

class MailChannel implements ChannelInterface
{
    /**
     * Set the configured message
     *
     * @param Message $message
     * @return void
     */
    public function __construct(
        private readonly Message $message
    ) {
    }

    /**
     * Send the notification to mail
     *
     * @return void
     */
    public function send(): void
    {
        Mail::getInstance()->send($this->message);
    }
}
