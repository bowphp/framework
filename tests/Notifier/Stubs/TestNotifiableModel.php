<?php

namespace Bow\Tests\Notifier\Stubs;

use Bow\Database\Barry\Model;
use Bow\Notifier\WithNotifier;

class TestNotifiableModel extends Model
{
    use WithNotifier;
}
