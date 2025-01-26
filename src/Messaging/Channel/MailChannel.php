<?php

namespace Bow\Messaging\Channel;

use Bow\Mail\Mail;
use Bow\Mail\Envelop;
use Bow\Messaging\Messaging;
use Bow\Database\Barry\Model;
use Bow\Messaging\Contracts\ChannelInterface;

class MailChannel implements ChannelInterface
{
    /**
     * Send the notification to mail
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toMail')) {
            return;
        }

        $envelop = $message->toMail($context);

        Mail::getInstance()->send($envelop);
    }
}
