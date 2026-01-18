<?php

namespace Bow\Notifier;

trait WithNotifier
{
    /**
     * Send message from authenticate user
     *
     * @param  Notifier $notifier
     * @return void
     */
    public function sendMessage(Notifier $notifier): void
    {
        $notifier->process($this);
    }

    /**
     * Send message on queue
     *
     * @param  Notifier $notifier
     * @return void
     */
    public function setMessageQueue(Notifier $notifier): void
    {
        $queue_job = new NotifierQueueJob($this, $notifier);

        queue($queue_job);
    }

    /**
     * Send message on specific queue
     *
     * @param  string    $queue
     * @param  Notifier $notifier
     * @return void
     */
    public function sendMessageQueueOn(string $queue, Notifier $notifier): void
    {
        $queue_job = new NotifierQueueJob($this, $notifier);

        $queue_job->setQueue($queue);

        queue($queue_job);
    }

    /**
     * Send mail later
     *
     * @param  integer   $delay
     * @param  Notifier $notifier
     * @return void
     */
    public function sendMessageLater(int $delay, Notifier $notifier): void
    {
        $queue_job = new NotifierQueueJob($this, $notifier);

        $queue_job->setDelay($delay);

        queue($queue_job);
    }

    /**
     * Send mail later on specific queue
     *
     * @param  integer   $delay
     * @param  string    $queue
     * @param  Notifier $notifier
     * @return void
     */
    public function sendMessageLaterOn(int $delay, string $queue, Notifier $notifier): void
    {
        $queue_job = new NotifierQueueJob($this, $notifier);

        $queue_job->setQueue($queue);
        $queue_job->setDelay($delay);

        queue($queue_job);
    }
}
