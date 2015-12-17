<?php

namespace System\Core;

use System\Interfaces\CollectionAccess;


class AppConfiguration implements CollectionAccess
{

    public function load($class, $config)
    {
        call_user_func([$class, "configure"], $config);
    }

    public function isKey($key)
    {
        // TODO: Implement isKey() method.
    }

    public function IsEmpty()
    {
        // TODO: Implement IsEmpty() method.
    }

    public function get($key = null)
    {
        // TODO: Implement get() method.
    }

    public function add($key, $data, $next = false)
    {
        // TODO: Implement add() method.
    }

    public function remove($key)
    {
        // TODO: Implement remove() method.
    }

    public function set($key, $value)
    {
        // TODO: Implement set() method.
    }

}