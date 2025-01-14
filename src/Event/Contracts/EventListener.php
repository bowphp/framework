<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

interface EventListener
{
    /**
     * Process the event
     *
     * @param AppEvent $event
     * @return mixed
     */
    public function process(AppEvent $event): void;
}
