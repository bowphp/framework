<?php

namespace Bow\Notification\Channel;

use Bow\Notification\Channel\ChannelInterface;

class DatabaseChannel implements ChannelInterface
{
    /**
     * Send the notification to database
     *
     * @param mixed $message
     * @return void
     */
    public function send(mixed $message)
    {
    }
}
