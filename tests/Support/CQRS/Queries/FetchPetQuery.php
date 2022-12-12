<?php

namespace Bow\Tests\Support\CQRS\Queries;

use Bow\Support\CQRS\Query\QueryInterface;

class FetchPetQuery implements QueryInterface
{
    public function __construct(public int $id)
    {
    }
}
