<?php

namespace Bow\Messaging\Channel;

use Bow\Database\Barry\Model;
use Bow\Mail\Mail;
use Bow\Mail\Message;
use Bow\Messaging\Contracts\ChannelInterface;

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
    )
    {
    }

    /**
     * Send the notification to mail
     *
     * @param Model $notifiable
     * @return void
     */
    public function send(Model $notifiable): void
    {
        Mail::getInstance()->send($this->message);
    }
}
