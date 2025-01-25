<?php

namespace Bow\Messaging;

use Bow\Database\Barry\Model;
use Bow\Mail\Envelop;
use Bow\Messaging\Channel\DatabaseChannel;
use Bow\Messaging\Channel\MailChannel;

abstract class Messaging
{
    /**
     * Defines the available channel
     *
     * @var array
     */
    private array $channels = [
        "mail" => MailChannel::class,
        "database" => DatabaseChannel::class,
    ];

    /**
     * Send notification to mail
     *
     * @param Model $context
     * @return Message|null
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
     * @return array
     */
    public function toSms(Model $context): array
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
            if (array_key_exists($channel, $this->channels)) {
                $result = $this->{"to" . ucfirst($channel)}($context);
                $target_channel = new $this->channels[$channel]($result);
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
