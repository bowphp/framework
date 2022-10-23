<?php

namespace Bow\Database\Barry\Traits;

trait ArrayAccessTrait
{
    /**
     * _offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * _offsetExists
     *
     * @param mixed $offset
     * @see http://php.net/manual/fr/class.arrayaccess.php
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * _offsetUnset
     *
     * @param mixed $offset
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * _offsetGet
     *
     * @param mixed $offset
     * @return mixed|null
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->attributes[$offset])
            ? $this->attributes[$offset]
            : null;
    }
}
