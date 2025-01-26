<?php

namespace Bow\Messaging\Channel;

use Bow\Database\Database;
use Bow\Messaging\Messaging;
use Bow\Database\Barry\Model;
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
     * @param Messaging $message
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toDatabase')) {
            return;
        }

        $database = $message->toDatabase($context);

        Database::table('notifications')->insert([
            'id' => str_uuid(),
            'data' => $database['data'],
            'concern_id' => $context->getKey(),
            'concern_type' => get_class($context),
            'type' => $database['type'],
        ]);
    }
}
