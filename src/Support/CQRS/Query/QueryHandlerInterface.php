<?php

declare(strict_types=1);

namespace Bow\Support\CQRS\Query;

use Bow\Support\CQRS\Query\QueryInterface;

interface QueryHandlerInterface
{
    /**
     * Handle the query
     *
     * @param QueryInterface $query
     * @return mixed
     */
    public function process(QueryInterface $query): mixed;
}
