<?php

namespace Bow\Database\Barry\Traits;

trait CanSerialized
{
    /**
     * __sleep
     *
     * @return string
     */
    public function __sleep()
    {
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
