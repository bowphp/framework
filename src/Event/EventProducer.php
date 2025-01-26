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
     * @param mixed $payload
     */
    public function __construct(
        private readonly mixed $event,
        private readonly mixed $payload = null,
    )
    {
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
