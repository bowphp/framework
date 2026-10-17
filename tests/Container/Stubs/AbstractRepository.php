<?php

namespace Bow\Tests\Container\Stubs;

abstract class AbstractRepository
{
    abstract public function find(int $id): ?array;
}
