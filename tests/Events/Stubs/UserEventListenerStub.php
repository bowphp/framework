<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Event\Contracts\EventListener;

class UserEventListenerStub implements EventListener
{
    private string $cache_filename;

    public function __construct()
    {
        $this->cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';
    }

    /**
     * Process the event emited
     *
     * @param mixed $event
     * @return void
     */
    public function process($payload = null): void
    {
        dd($payload);
        file_put_contents($this->cache_filename, '');
        file_put_contents($this->cache_filename, 'dispatched');
    }
}
