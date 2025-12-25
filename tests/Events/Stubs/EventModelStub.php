<?php

namespace Bow\Tests\Events\Stubs;

use Bow\Database\Barry\Model;

class EventModelStub extends Model
{
    protected string $table = 'events';

    protected string $primarey_key = 'id';

    protected ?string $connection = 'mysql';
}
