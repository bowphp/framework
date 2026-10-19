<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Model;

/**
 * A second model class, to check that a query on another model neither
 * hides nor causes a leak from the first one.
 */
class BuilderOtherRowModelStub extends Model
{
    protected string $table = 'builder_other_rows';

    protected bool $timestamps = false;
}
