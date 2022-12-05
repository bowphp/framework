<?php

namespace Bow\Tests\Auth\Stubs;

use Bow\Auth\Authentication;

class UserModelStub extends Authentication
{
    protected string $table = "users";

    protected array $hidden = [
        "password"
    ];
}
