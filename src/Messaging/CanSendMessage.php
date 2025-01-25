<?php

namespace Bow\Messaging;

trait CanSendMessage
{
    /**
     * Send message from authenticate user
     *
     * @param Messaging $message
     * @return void
     */
    public function sendMessage(Messaging $message): void
    {
        $message->process($this);
    }

    /**
     * Send message on queue
     *
     * @param Messaging $message
     * @return void
     */
    public function setMessageQueue(Messaging $message): void
    {
        $producer = new MessagingQueueProducer($this, $message);

        queue($producer);
    }

    /**
     * Send message on specific queue
     *
     * @param string $queue
     * @param Messaging $message
     * @return void
     */
    public function sendMessageQueueOn(string $queue, Messaging $message): void
    {
        $producer = new MessagingQueueProducer($this, $message);

        $producer->setQueue($queue);

        queue($producer);
    }

    /**
     * Send mail later
     *
     * @param integer $delay
     * @param Messaging $message
     * @return void
     */
    public function sendMessageLater(int $delay, Messaging $message): void
    {
        $producer = new MessagingQueueProducer($this, $message);

        $producer->setDelay($delay);

        queue($producer);
    }

    /**
     * Send mail later on specific queue
     *
     * @param integer $delay
     * @param string $queue
     * @param Messaging $message
     * @return void
     */
    public function sendMessageLaterOn(int $delay, string $queue, Messaging $message): void
    {
        $producer = new MessagingQueueProducer($this, $message);

        $producer->setQueue($queue);
        $producer->setDelay($delay);

        queue($producer);
    }
}
