<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Relations\HasMany;
use Bow\Tests\Database\Stubs\PetModelStub;

class PetMasterModelStub extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected string $table = "pet_masters";

    /**
     * @var string
     */
    protected string $primarey_key = 'id';

    /**
     * @var bool
     */
    protected bool $timestamps = false;

    /**
     * Get the list of pets
     *
     * @return HasMany
     */
    public function pets(): HasMany
    {
        return $this->hasMany(PetModelStub::class);
    }
}
