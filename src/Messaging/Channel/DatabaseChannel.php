<?php

namespace Bow\Messaging\Channel;

use Bow\Database\Barry\Model;
use Bow\Database\Database;
use Bow\Messaging\Contracts\ChannelInterface;

class DatabaseChannel implements ChannelInterface
{
    public function __construct(
        private readonly array $database
    ) {
    }

    /**
     * Send the notification to database
     *
     * @param Model $notifiable
     * @return void
     */
    public function send(Model $notifiable): void
    {
        Database::table('notifications')->insert([
            'id' => str_uuid(),
            'data' => $this->database['data'],
            'concern_id' => $notifiable->getKey(),
            'concern_type' => get_class($notifiable),
            'type' => $this->database['type'],
        ]);
    }
}
