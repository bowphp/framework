<?php

namespace Bow\Database\Barry\Relations\Traits;

trait Belongs
{
    /**
     * Make belongs to one relation
     *
     * @param string $class
     * @param string $primary_key
     * @param string $second_key
     */
    public function belongsTo($class, $primary_key, $second_key)
    {
    }

    /**
     * Make belongs to many relation
     *
     * @param string $class
     * @param string $primary_key
     * @param string $second_key
     */
    public function belongsToMany($class, $primary_key, $second_key)
    {
    }
}
