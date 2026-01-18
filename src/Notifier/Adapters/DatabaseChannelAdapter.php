<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Database\Database;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;

class DatabaseChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Send the notification to database
     *
     * @param Model     $context
     * @param Notifier $message
     */
    public function send(Model $context, Notifier $message): void
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
