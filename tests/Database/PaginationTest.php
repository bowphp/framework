<?php

declare(strict_types=1);

namespace Tests\Bow\Database;

use PHPUnit\Framework\TestCase;
use Bow\Database\Pagination;

class PaginationTest extends TestCase
{
    private Pagination $pagination;

    protected function setUp(): void
    {
        $this->pagination = new Pagination(
            next: 2,
            previous: 0,
            total: 3,
            perPage: 10,
            current: 1,
            data: collect(['item1', 'item2', 'item3'])
        );
    }

    public function test_next(): void
    {
        $this->assertSame(2, $this->pagination->next());
    }

    public function test_previous(): void
    {
        $this->assertSame(0, $this->pagination->previous());
    }

    public function test_current(): void
    {
        $this->assertSame(1, $this->pagination->current());
    }

    public function test_items(): void
    {
        $this->assertSame(['item1', 'item2', 'item3'], $this->pagination->items()->toArray());
    }

    public function test_total(): void
    {
        $this->assertSame(3, $this->pagination->total());
    }
}
