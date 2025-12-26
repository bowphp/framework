<?php

namespace Bow\Messaging\Adapters;

use Bow\Database\Barry\Model;
use Bow\Database\Database;
use Bow\Messaging\Contracts\ChannelAdapterInterface;
use Bow\Messaging\Messaging;

class DatabaseChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Send the notification to database
     *
     * @param Model     $context
     * @param Messaging $message
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toDatabase')) {
            return;
        }

        $database = $message->toDatabase($context);

        if ($database === null) {
            throw new \RuntimeException(
                "The database notification returned by toDatabase() cannot be null."
            );
        }

        $table_name = config('messaging.notification.table');

        $table = Database::connection($context->getConnection())->table($table_name ?? 'notifications');

        $notification = [
            'data' => json_encode($database['data']),
            'concern_id' => $context->getKey(),
            'concern_type' => get_class($context),
            'type' => $database['type'] ?? 'notification',
        ];

        $table->insert($notification);
    }
}
