<?php

namespace Bow\Messaging\Adapters;

use Bow\Database\Barry\Model;
use Bow\Mail\Mail;
use Bow\Messaging\Contracts\ChannelAdapterInterface;
use Bow\Messaging\Messaging;

class MailChannelAdapter implements ChannelAdapterInterface
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
