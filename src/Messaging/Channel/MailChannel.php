<?php

namespace Bow\Messaging\Channel;

use Bow\Database\Barry\Model;
use Bow\Mail\Mail;
use Bow\Mail\Envelop;
use Bow\Messaging\Contracts\ChannelInterface;

class MailChannel implements ChannelInterface
{
    /**
     * Set the configured message
     *
     * @param Envelop $envelop
     * @return void
     */
    public function __construct(
        private readonly Envelop $envelop
    ) {
    }

    /**
     * Send the notification to mail
     *
     * @param Model $context
     * @return void
     */
    public function send(Model $context): void
    {
        Mail::getInstance()->send($this->envelop);
    }
}
