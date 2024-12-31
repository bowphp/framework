<?php

namespace Bow\Notification;

trait CanSendNotification
{
    public function sendNotification(Notification $notification)
    {
        $notification->process($this);
    }
}
