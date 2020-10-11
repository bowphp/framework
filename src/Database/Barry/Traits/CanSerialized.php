<?php

namespace Bow\Database\Barry\Traits;

use Bow\Database\Barry\Model;

trait CanSerialized
{
    /**
     * __sleep
     *
     * @return string
     */
    public function __sleep()
    {
        if ($this instanceof Model) {
            return ['attributes' => $this->attributes];
        }

        return $this->toArray();
    }

    /**
     * __wakeup
     *
     * @return string
     */
    public function __wakeup()
    {
    }
}
