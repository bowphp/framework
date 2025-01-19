<?php

namespace Bow\Messaging\Contracts;

use Bow\Database\Barry\Model;

interface ChannelInterface
{
    /**
     * Send the notification
     *
     * @param Model $notifiable
     * @return void
     */
    public function send(Model $notifiable): void;
}
