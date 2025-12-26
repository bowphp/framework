<?php

namespace Bow\Messaging;

trait SendMessaging
{
    /**
     * Send message from authenticate user
     *
     * @param  Messaging $message
     * @return void
     */
    public function sendMessage(Messaging $message): void
    {
        $message->process($this);
    }

    /**
     * Send message on queue
     *
     * @param  Messaging $message
     * @return void
     */
    public function setMessageQueue(Messaging $message): void
    {
        $queue_job = new MessagingQueueJob($this, $message);

        queue($queue_job);
    }

    /**
     * Send message on specific queue
     *
     * @param  string    $queue
     * @param  Messaging $message
     * @return void
     */
    public function sendMessageQueueOn(string $queue, Messaging $message): void
    {
        $queue_job = new MessagingQueueJob($this, $message);

        $queue_job->setQueue($queue);

        queue($queue_job);
    }

    /**
     * Send mail later
     *
     * @param  integer   $delay
     * @param  Messaging $message
     * @return void
     */
    public function sendMessageLater(int $delay, Messaging $message): void
    {
        $queue_job = new MessagingQueueJob($this, $message);

        $queue_job->setDelay($delay);

        queue($queue_job);
    }

    /**
     * Send mail later on specific queue
     *
     * @param  integer   $delay
     * @param  string    $queue
     * @param  Messaging $message
     * @return void
     */
    public function sendMessageLaterOn(int $delay, string $queue, Messaging $message): void
    {
        $queue_job = new MessagingQueueJob($this, $message);

        $queue_job->setQueue($queue);
        $queue_job->setDelay($delay);

        queue($queue_job);
    }
}
