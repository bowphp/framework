<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Relations\BelongsTo;

class PetModelStub extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "pets";

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
