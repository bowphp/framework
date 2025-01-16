<?php

namespace Bow\Database;

use Bow\Support\Collection as SupportCollection;
use Bow\Database\Collection as DatabaseCollection;

class Pagination
{
    public function __construct(
        private readonly int $next,
        private readonly int $previous,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $current,
        private readonly SupportCollection|DatabaseCollection $data
    ) {
    }

    public function next(): int
    {
        return $this->next;
    }

    public function hasNext(): bool
    {
        return $this->next != 0;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function previous(): int
    {
        return $this->previous;
    }

    public function hasPrevious(): bool
    {
        return $this->previous != 0;
    }

    public function current(): int
    {
        return $this->current;
    }

    public function items(): SupportCollection|DatabaseCollection
    {
        return $this->data;
    }

    public function total(): int
    {
        return $this->total;
    }
}
