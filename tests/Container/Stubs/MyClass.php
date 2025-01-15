<?php

namespace Bow\Tests\Container\Stubs;

class MyClass
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function getCollection()
    {
        return $this->collection;
    }
}
