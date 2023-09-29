<?php

namespace Bow\Event;

use Bow\Event\Contracts\EventListener;
use Bow\Event\Contracts\EventShouldQueue;
use Bow\Queue\ProducerService;

class EventProducer extends ProducerService
{
    /**
     * EventProducer constructor
     * 
     * @param EventListener|EventShouldQueue $event
     */
    public function __construct(
        private mixed $event,
        private mixed $payload = null,
    ) {
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
