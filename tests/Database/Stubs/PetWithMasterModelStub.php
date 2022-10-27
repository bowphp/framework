<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Relations\BelongsTo;
use Bow\Tests\Database\Stubs\PetMasterModelStub;

class PetWithMasterModelStub extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "pet_with_masters";

    /**
     * @var string
     */
    protected string $primarey_key = 'id';

    /**
     * @var bool
     */
    protected bool $timestamps = false;

    /**
     * Build the relation with master class
     *
     * @return BelongsTo
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(PetMasterModelStub::class, "master_id");
    }
}
