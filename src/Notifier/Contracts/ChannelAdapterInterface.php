<?php

namespace Bow\Notifier\Contracts;

use Bow\Database\Barry\Model;
use Bow\Notifier\Notifier;

interface ChannelAdapterInterface
{
    /**
     * Send a message through the channel
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     */
    public function send(Model $context, Notifier $notifier): void;
}
