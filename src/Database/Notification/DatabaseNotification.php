<?php

namespace Bow\Database\Notification;

use Bow\Database\Barry\Model;
use Bow\Database\Database;

class DatabaseNotification extends Model
{
    /**
     * Cast data as json
     *
     * @var array|string[]
     */
    protected array $casts = [
        'data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('notification.table') ?: 'notifications';
    }

    /**
     * Mark notification as read
     *
     * @return bool|int
     */
    public function markAsRead(): bool|int
    {
        return $this->update(['read_at' => app_now()]);
    }
}
