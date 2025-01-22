<?php

namespace Bow\Messaging;

use Bow\Database\Barry\Model;
use Bow\Mail\Message;
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
     * @param Model $notifiable
     * @return Message|null
     */
    public function toMail(Model $notifiable): ?Message
    {
        return new Message();
    }

    /**
     * Send notification to database
     *
     * @param Model $notifiable
     * @return array
     */
    public function toDatabase(Model $notifiable): array
    {
        return [];
    }

    /**
     * Send notification to sms
     *
     * @param Model $notifiable
     * @return array
     */
    public function toSms(Model $notifiable): array
    {
        return [];
    }

    /**
     * Process the notification
     * @param Model $notifiable
     * @return void
     */
    final function process(Model $notifiable): void
    {
        $channels = $this->channels($notifiable);

        foreach ($channels as $channel) {
            if (array_key_exists($channel, $this->channels)) {
                $result = $this->{"to" . ucfirst($channel)}($notifiable);
                $target_channel = new $this->channels[$channel]($result);
                $target_channel->send($notifiable);
            }
        }
    }

    /**
     * Returns the available channels to be used
     *
     * @param Model $notifiable
     * @return array
     */
    abstract public function channels(Model $notifiable): array;
}
