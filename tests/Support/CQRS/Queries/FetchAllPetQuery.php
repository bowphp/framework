<?php

namespace Bow\Tests\Support\CQRS\Queries;

use Bow\Support\CQRS\Query\QueryInterface;

class FetchAllPetQuery implements QueryInterface
{
    public function __construct()
    {
    }
}
