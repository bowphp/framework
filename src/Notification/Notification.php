<?php

namespace Bow\Notification;

use Bow\Database\Barry\Model;
use Bow\Mail\Message;
use Bow\Notification\Channel\MailChannel;
use Bow\Notification\Channel\DatabaseChannel;

abstract class Notification
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
     * Returns the available channels to be used
     *
     * @param \Bow\Database\Barry\Model $notifiable
     * @return array
     */
    abstract public function channels(Model $notifiable): array;

    /**
     * Send notification to mail
     *
     * @param \Bow\Database\Barry\Model $notifiable
     * @return mixed
     */
    public function toMail(Model $notifiable): ?Message
    {
        $message = new Message();

        return $message;
    }

    /**
     * Send notification to database
     *
     * @param \Bow\Database\Barry\Model $notifiable
     * @return array
     */
    public function toDatabase(Model $notifiable): array
    {
        return [];
    }

    /**
     * Process the notification
     * @param \Bow\Database\Barry\Model $notifiable
     * @return void
     */
    final function process(Model $notifiable)
    {
        $channels = $this->channels($notifiable);

        foreach ($channels as $channel) {
            if (array_key_exists($channel, $this->channels)) {
                $result = $this->{"to" . ucfirst($channel)}($notifiable);
                $target_channel = new $this->channels[$channel]();
                $target_channel->send($result);
            }
        }
    }
}
