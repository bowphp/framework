<?php

namespace Bow\Notification\Channel;

use Bow\Mail\Mail;
use Bow\Mail\Message;
use Bow\Notification\Channel\ChannelInterface;

class MailChannel implements ChannelInterface
{
    /**
     * Send the notification to mail
     *
     * @param mixed $message
     * @return void
     */
    public function send(mixed $message)
    {
        if ($message instanceof Message) {
            Mail::getInstance()->send($message);
        }
    }
}
