<?php

declare(strict_types=1);

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Model;
use Bow\Database\Barry\Traits\SoftDelete;

class SoftDeletePetModelStub extends Model
{
    use SoftDelete;

    /**
     * @var string
     */
    protected string $table = "pets";

    /**
     * @var bool
     */
    protected bool $timestamps = false;
}
