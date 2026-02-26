<?php

namespace Bow\Tests\Notifier\Stubs;

use Bow\Database\Barry\Model;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;

class MockChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Store sent notifications for assertions
     *
     * @var array
     */
    public static array $sent = [];

    /**
     * Send the notification (mock - just records it)
     *
     * @param Model $context
     * @param Notifier $notifier
     * @return void
     */
    public function send(Model $context, Notifier $notifier): void
    {
        static::$sent[] = [
            'context' => $context,
            'notifier' => $notifier,
        ];
    }

    /**
     * Reset sent notifications
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$sent = [];
    }
}
