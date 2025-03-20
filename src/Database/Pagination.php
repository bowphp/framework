<?php

namespace Bow\Database;

use Bow\Database\Collection as DatabaseCollection;
use Bow\Support\Collection as SupportCollection;

class Pagination
{
    /**
     * Pagination constructor.
     *
     * @param int                                  $next     The next page number.
     * @param int                                  $previous The previous page number.
     * @param int                                  $total    The total number of items.
     * @param int                                  $perPage  The number of items per page.
     * @param int                                  $current  The current page number.
     * @param SupportCollection|DatabaseCollection $data     The collection of items for the current page.
     */
    public function __construct(
        private readonly int $next,
        private readonly int $previous,
        private readonly int $total,
        private readonly int $perPage,
        private readonly int $current,
        private readonly SupportCollection|DatabaseCollection $data
    ) {
    }

    /**
     * Get the next page number.
     *
     * @return int
     */
    public function next(): int
    {
        return $this->next;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->next != 0;
    }

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the previous page number.
     *
     * @return int
     */
    public function previous(): int
    {
        return $this->previous;
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->previous != 0;
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function current(): int
    {
        return $this->current;
    }

    /**
     * Get the collection of items for the current page.
     *
     * @return SupportCollection|DatabaseCollection
     */
    public function items(): SupportCollection|DatabaseCollection
    {
        return $this->data;
    }

    /**
     * Get the total number of items.
     *
     * @return int
     */
    public function total(): int
    {
        return $this->total;
    }
}
