<?php

namespace Bow\Tests\Database\Stubs;

use Bow\Database\Barry\Model;

/**
 * A row with a CHECK constraint on `status`, so a test can make an UPDATE
 * fail on purpose and watch what the next statement on the model inherits.
 */
class BuilderProbeRowModelStub extends Model
{
    protected string $table = 'builder_probe_rows';

    protected bool $timestamps = false;
}
