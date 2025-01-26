<?php

namespace Bow\Messaging\Contracts;

use Bow\Database\Barry\Model;
use Bow\Messaging\Messaging;

interface ChannelInterface
{
    /**
     * Send a message through the channel
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     */
    public function send(Model $context, Messaging $message): void;
}
