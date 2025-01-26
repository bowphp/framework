<?php

namespace Bow\Messaging;

use Bow\Database\Barry\Model;
use Bow\Mail\Envelop;
use Bow\Messaging\Adapters\DatabaseChannelAdapter;
use Bow\Messaging\Adapters\MailChannelAdapter;
use Bow\Messaging\Adapters\SlackChannelAdapter;
use Bow\Messaging\Adapters\SmsChannelAdapter;
use Bow\Messaging\Adapters\TelegramChannelAdapter;

abstract class Messaging
{
    /**
     * Defines the available channel
     *
     * @var array
     */
    private static array $channels = [
        "mail" => MailChannelAdapter::class,
        "database" => DatabaseChannelAdapter::class,
        "telegram" => TelegramChannelAdapter::class,
        "slack" => SlackChannelAdapter::class,
        "sms" => SmsChannelAdapter::class,
    ];

    /**
     * Push channels to the messaging
     *
     * @param array $channels
     * @return array
     */
    public static function pushChannels(array $channels): array
    {
        static::$channels = array_merge(static::$channels, $channels);

        return self::$channels;
    }

    /**
     * Send notification to mail
     *
     * @param Model $context
     * @return Envelop|null
     */
    public function toMail(Model $context): ?Envelop
    {
        return null;
    }

    /**
     * Send notification to database
     *
     * @param Model $context
     * @return array
     */
    public function toDatabase(Model $context): array
    {
        return [];
    }

    /**
     * Send notification to sms
     *
     * @param Model $context
     * @return array{to: string, message: string}
     */
    public function toSms(Model $context): array
    {
        return [];
    }

    /**
     * Send notification to slack
     *
     * @param Model $context
     * @return array{webhook_url: ?string, content: array}
     */
    public function toSlack(Model $context): array
    {
        return [];
    }

    /**
     * Send notification to telegram
     *
     * @param Model $context
     * @return array{message: string, chat_id: string, parse_mode: string}
     */
    public function toTelegram(Model $context): array
    {
        return [];
    }

    /**
     * Process the notification
     * @param Model $context
     * @return void
     */
    final function process(Model $context): void
    {
        $channels = $this->channels($context);

        foreach ($channels as $channel) {
            if (array_key_exists($channel, static::$channels)) {
                $target_channel = new static::$channels[$channel]();
                $target_channel->send($context);
            }
        }
    }

    /**
     * Returns the available channels to be used
     *
     * @param Model $context
     * @return array
     */
    abstract public function channels(Model $context): array;
}
