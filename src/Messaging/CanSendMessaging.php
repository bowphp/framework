<?php

namespace Bow\Messaging;

trait CanSendMessaging
{
    /**
     * Send message from authenticate user
     *
     * @param Messaging $notification
     * @return void
     */
    public function sendMessage(Messaging $notification): void
    {
        $notification->process($this);
    }
}
