<?php

namespace Bow\Tests\Stubs\Container;

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
