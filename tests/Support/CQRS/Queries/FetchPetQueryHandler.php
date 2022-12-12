<?php

use Bow\Support\CQRS\Query\QueryInterface;
use Bow\Tests\Database\Stubs\PetModelStub;
use Bow\Support\CQRS\Query\QueryHandlerInterface;
use Bow\Tests\Support\CQRS\Queries\FetchPetQuery;

class FetchPetQueryHandler implements QueryHandlerInterface
{
    public function process(QueryInterface $query): mixed
    {
        $pet = PetModelStub::find($query->id);

        return $pet;
    }
}
