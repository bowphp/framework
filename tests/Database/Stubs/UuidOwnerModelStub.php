<?php

namespace Bow\Tests\Database\Stubs;

class UuidOwnerModelStub extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "uuid_owners";

    /**
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * @var string
     */
    protected string $primary_key_type = 'string';

    /**
     * @var bool
     */
    protected bool $timestamps = false;
}
