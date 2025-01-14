<?php

namespace Bow\Database;

use Bow\Support\Collection as SupportCollection;
use Bow\Database\Collection as DatabaseCollection;

class Pagination
{
    public function __construct(
        private int $next,
        private int $previous,
        private int $total,
        private int $perPage,
        private int $current,
        private SupportCollection|DatabaseCollection $data
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
