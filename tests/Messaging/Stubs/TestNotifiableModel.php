<?php

namespace Bow\Tests\Messaging\Stubs;

use Bow\Database\Barry\Model;
use Bow\Messaging\CanSendMessage;

class TestNotifiableModel extends Model
{
    use CanSendMessage;
} 
