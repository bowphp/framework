<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Relations\BelongsTo;

class UuidItemModelStub extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "uuid_items";

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

    /**
     * The owner of the item (nullable foreign key).
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(UuidOwnerModelStub::class, "owner_id");
    }
}
