<?php

namespace Bow\Event;

use Bow\Event\Contracts\EventListener;
use Bow\Event\Contracts\EventShouldQueue;
use Bow\Queue\QueueTask;

class EventQueueTask extends QueueTask
{
    /**
     * EventQueueTask constructor
     *
     * @param EventListener|EventShouldQueue $event
     * @param mixed                          $payload
     */
    public function __construct(
        private EventListener|EventShouldQueue $event,
        private mixed $payload = null,
    ) {
        parent::__construct();
    }

    /**
     * Process event
     *
     * @return void
     */
    public function process(): void
    {
        $this->event->process($this->payload);
    }
}
