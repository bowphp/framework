<?php

namespace Bow\Tests\Messaging\Stubs;

use Bow\Database\Barry\Model;
use Bow\Messaging\SendMessaging;

class TestNotifiableModel extends Model
{
    use SendMessaging;
}
