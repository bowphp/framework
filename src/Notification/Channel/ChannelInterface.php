<?php

namespace Bow\Notification\Channel;

interface ChannelInterface
{
    /**
     * Send the notification
     *
     * @return void
     */
    public function send(): void;
}
