<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

use Bow\Database\Barry\Model;

trait CanSerialized
{
    /**
     * __sleep
     *
     * @return string
     */
    public function __sleep(): array
    {
        if ($this instanceof Model) {
            return ['attributes' => $this->attributes];
        }

        return ['attributes' => $this->toArray()];
    }
}
