<?php

namespace Bow\Database\Notification;

use Bow\Database\Exception;
use Bow\Database\Exception\ConnectionException;

trait WithNotification
{
    /**
     * @throws ConnectionException|Exception\QueryBuilderException
     */
    public function notifications()
    {
        return (new DatabaseNotification())
            ->where('concern_id', $this->getKeyValue())
            ->where('concern_type', get_class($this));
    }

    /**
     * @throws ConnectionException|Exception\QueryBuilderException
     */
    public function markAsRead(string $notification_id)
    {
       return $this->notifications()->where('id', $notification_id)->update(['read_at' => app_now()]);
    }

    /**
     * @throws ConnectionException|Exception\QueryBuilderException
     */
    public function markAllAsRead()
    {
        return $this->notifications()->update(['read_at' => app_now()]);
    }
}
