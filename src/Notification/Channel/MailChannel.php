<?php

namespace Bow\Notification\Channel;

use Bow\Mail\Mail;
use Bow\Mail\Message;

class MailChannel implements ChannelInterface
{
    /**
     * Send the notification to mail
     *
     * @param mixed $message
     * @return void
     */
    public function send(mixed $message): void
    {
        if ($message instanceof Message) {
            Mail::getInstance()->send($message);
        }
    }
}
