<?php

namespace Bow\Notifier;

trait WithNotifier
{
    /**
     * Send message from authenticate user
     *
     * @param  Notifier $message
     * @return void
     */
    public function sendMessage(Notifier $message): void
    {
        $message->process($this);
    }

    /**
     * Send message on queue
     *
     * @param  Notifier $message
     * @return void
     */
    public function setMessageQueue(Notifier $message): void
    {
        $queue_job = new NotifierQueueJob($this, $message);

        queue($queue_job);
    }

    /**
     * Send message on specific queue
     *
     * @param  string    $queue
     * @param  Notifier $message
     * @return void
     */
    public function sendMessageQueueOn(string $queue, Notifier $message): void
    {
        $queue_job = new NotifierQueueJob($this, $message);

        $queue_job->setQueue($queue);

        queue($queue_job);
    }

    /**
     * Send mail later
     *
     * @param  integer   $delay
     * @param  Notifier $message
     * @return void
     */
    public function sendMessageLater(int $delay, Notifier $message): void
    {
        $queue_job = new NotifierQueueJob($this, $message);

        $queue_job->setDelay($delay);

        queue($queue_job);
    }

    /**
     * Send mail later on specific queue
     *
     * @param  integer   $delay
     * @param  string    $queue
     * @param  Notifier $message
     * @return void
     */
    public function sendMessageLaterOn(int $delay, string $queue, Notifier $message): void
    {
        $queue_job = new NotifierQueueJob($this, $message);

        $queue_job->setQueue($queue);
        $queue_job->setDelay($delay);

        queue($queue_job);
    }
}
