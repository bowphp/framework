<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Mail\Mail;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;

class MailChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Send the notification to mail
     *
     * @param  Model     $context
     * @param  Notifier $message
     * @return void
     */
    public function send(Model $context, Notifier $message): void
    {
        if (!method_exists($message, 'toMail')) {
            return;
        }

        $envelop = $message->toMail($context);

        if ($envelop === null) {
            throw new \RuntimeException(
                "The mail notification returned by toMail() cannot be null."
            );
        }

        Mail::getInstance()->send($envelop);
    }
}
