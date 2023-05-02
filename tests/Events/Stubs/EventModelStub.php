<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Database\Barry\Model;

class EventModelStub extends Model
{
    protected string $table = 'events';

    protected string $primarey_key = 'id';

    protected ?string $connection = 'mysql';

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';
        file_put_contents($cache_filename, '');

        EventModelStub::created(function ($event_model) use ($cache_filename) {
            file_put_contents($cache_filename, 'created');
        });

        EventModelStub::deleted(function ($event_model) use ($cache_filename) {
            file_put_contents($cache_filename, 'deleted');
        });

        EventModelStub::updated(function ($event_model) use ($cache_filename) {
            file_put_contents($cache_filename, 'updated');
        });
    }
}
