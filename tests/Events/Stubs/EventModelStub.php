<?php

namespace Bow\Tests\Events;

class EventModelStub extends \Bow\Database\Barry\Model
{
    protected string $table = 'pets';

    protected string $primarey_key = 'id';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

        file_put_contents($cache_filename, '');

        EventModelStub::created(function () use ($cache_filename) {
            file_put_contents($cache_filename, 'created');
        });

        EventModelStub::deleted(function () use ($cache_filename) {
            file_put_contents($cache_filename, 'deleted');
        });

        EventModelStub::updated(function () use ($cache_filename) {
            file_put_contents($cache_filename, 'updated');
        });
    }
}
