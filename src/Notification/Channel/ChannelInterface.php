<?php

namespace Bow\Notification\Channel;

interface ChannelInterface
{
    /**
     * Send the notification
     *
     * @param mixed $message
     * @return void
     */
    public function send(mixed $message);
}
