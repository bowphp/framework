<?php

namespace Bow\Notification;

trait CanSendNotification
{
    /**
     * Send notification from authenticate user
     *
     * @param Notification $notification
     * @return void
     */
    public function sendNotification(Notification $notification): void
    {
        $notification->process($this);
    }
}
