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
     * @param Model $context
     * @return void
     */
    public function send(Model $context): void
    {
        Database::table('notifications')->insert([
            'id' => str_uuid(),
            'data' => $this->database['data'],
            'concern_id' => $context->getKey(),
            'concern_type' => get_class($context),
            'type' => $this->database['type'],
        ]);
    }
}
