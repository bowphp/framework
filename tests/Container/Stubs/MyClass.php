<?php

namespace Bow\Tests\Stubs\Container;

use Bow\Support\Collection;

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
