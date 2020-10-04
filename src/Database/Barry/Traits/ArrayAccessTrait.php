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
    public function offsetSet($offset, $value)
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
    public function offsetExists($offset)
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
    public function offsetUnset($offset)
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
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset])
            ? $this->attributes[$offset]
            : null;
    }
}
