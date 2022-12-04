<?php

declare(strict_types=1);

namespace Bow\Event\Contracts;

abstract class ApplicationEvent
{
    /**
     * The event name
     *
     * @var string
     */
    protected string $name;

    /**
     * Retrieve the event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
