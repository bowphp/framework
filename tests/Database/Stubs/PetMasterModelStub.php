<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Relations\BelongsToMany;
use Bow\Database\Barry\Relations\HasMany;
use Bow\Database\Barry\Relations\HasOne;

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
        return $this->hasMany(PetModelStub::class, 'id', 'master_id');
    }

    /**
     * Get a single owned pet
     *
     * @return HasOne
     */
    public function firstPet(): HasOne
    {
        return $this->hasOne(PetModelStub::class, 'master_id', 'id');
    }

    /**
     * Get the list of pets through a belongs to many relation
     *
     * @return BelongsToMany
     */
    public function manyPets(): BelongsToMany
    {
        return $this->belongsToMany(PetModelStub::class, 'id', 'master_id');
    }
}
