<?php

namespace Bow\Database;

class Pagination
{
    public function __construct(
        private int $next,
        private int $previous,
        private int $total,
        private int $perPage,
        private int $current,
        private array $data
    ) {
    }

    public function next(): int
    {
        return $this->next;
    }

    public function previous(): int
    {
        return $this->next;
    }

    public function current(): int
    {
        return $this->current;
    }

    public function items(): array
    {
        return $this->data;
    }

    public function total(): array
    {
        return $this->data;
    }
}
