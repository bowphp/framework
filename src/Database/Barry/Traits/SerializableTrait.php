<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

trait SerializableTrait
{
    /**
     * Serialize model
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->attributes ?? [];
    }

    /**
     * Unserialize
     *
     * @param array $attributes
     */
    public function __unserialize(array $attributes): void
    {
        $this->setAttributes($attributes);
    }
}
