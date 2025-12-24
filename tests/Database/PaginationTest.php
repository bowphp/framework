<?php

declare(strict_types=1);

namespace Bow\Tests\Database;

use Bow\Database\Pagination;
use Bow\Support\Collection;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    /**
     * @dataProvider basicPaginationProvider
     */
    public function test_next(int $expectedNext, int $next, int $previous, int $total, int $perPage, int $current): void
    {
        $pagination = $this->createPagination($next, $previous, $total, $perPage, $current);
        $this->assertSame($expectedNext, $pagination->next());
    }

    /**
     * @dataProvider basicPaginationProvider
     */
    public function test_previous(int $expectedNext, int $next, int $previous, int $total, int $perPage, int $current): void
    {
        $pagination = $this->createPagination($next, $previous, $total, $perPage, $current);
        $this->assertSame($previous, $pagination->previous());
    }

    /**
     * @dataProvider basicPaginationProvider
     */
    public function test_current(int $expectedNext, int $next, int $previous, int $total, int $perPage, int $current): void
    {
        $pagination = $this->createPagination($next, $previous, $total, $perPage, $current);
        $this->assertSame($current, $pagination->current());
    }

    /**
     * @dataProvider basicPaginationProvider
     */
    public function test_total(int $expectedNext, int $next, int $previous, int $total, int $perPage, int $current): void
    {
        $pagination = $this->createPagination($next, $previous, $total, $perPage, $current);
        $this->assertSame($total, $pagination->total());
    }

    /**
     * @dataProvider basicPaginationProvider
     */
    public function test_per_page(int $expectedNext, int $next, int $previous, int $total, int $perPage, int $current): void
    {
        $pagination = $this->createPagination($next, $previous, $total, $perPage, $current);
        $this->assertSame($perPage, $pagination->perPage());
    }

    public function test_items_returns_collection(): void
    {
        $data = collect(['item1', 'item2', 'item3']);
        $pagination = new Pagination(
            next: 2,
            previous: 0,
            total: 3,
            perPage: 10,
            current: 1,
            data: $data
        );

        $items = $pagination->items();
        $this->assertInstanceOf(Collection::class, $items);
        $this->assertSame(['item1', 'item2', 'item3'], $items->toArray());
    }

    public function test_items_with_empty_collection(): void
    {
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 0,
            perPage: 10,
            current: 1,
            data: collect([])
        );

        $this->assertInstanceOf(Collection::class, $pagination->items());
        $this->assertEmpty($pagination->items()->toArray());
    }

    // ===== Navigation Helpers Tests =====

    /**
     * @dataProvider navigationHelpersProvider
     */
    public function test_has_next(bool $expectedHasNext, int $next): void
    {
        $pagination = $this->createPagination($next, 1, 3, 10, 2);
        $this->assertSame($expectedHasNext, $pagination->hasNext());
    }

    /**
     * @dataProvider navigationHelpersProvider
     */
    public function test_has_previous(bool $expectedHasPrevious, int $previous): void
    {
        $pagination = $this->createPagination(3, $previous, 3, 10, 2);
        $this->assertSame($expectedHasPrevious, $pagination->hasPrevious());
    }

    // ===== First Page Tests =====

    public function test_first_page_navigation(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 1,
            total: 5,
            perPage: 10,
            current: 1
        );

        $this->assertSame(1, $pagination->current());
        $this->assertSame(2, $pagination->next());
        $this->assertSame(1, $pagination->previous());
        $this->assertTrue($pagination->hasNext());
        $this->assertTrue($pagination->hasPrevious()); // previous is 1, not 0
    }

    public function test_first_page_with_no_next(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 1,
            total: 1,
            perPage: 10,
            current: 1
        );

        $this->assertFalse($pagination->hasNext());
        $this->assertTrue($pagination->hasPrevious());
    }

    // ===== Middle Page Tests =====

    public function test_middle_page_navigation(): void
    {
        $pagination = $this->createPagination(
            next: 3,
            previous: 1,
            total: 5,
            perPage: 10,
            current: 2
        );

        $this->assertSame(2, $pagination->current());
        $this->assertSame(3, $pagination->next());
        $this->assertSame(1, $pagination->previous());
        $this->assertTrue($pagination->hasNext());
        $this->assertTrue($pagination->hasPrevious());
    }

    // ===== Last Page Tests =====

    public function test_last_page_navigation(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 2,
            total: 3,
            perPage: 10,
            current: 3
        );

        $this->assertSame(3, $pagination->current());
        $this->assertSame(0, $pagination->next());
        $this->assertSame(2, $pagination->previous());
        $this->assertFalse($pagination->hasNext());
        $this->assertTrue($pagination->hasPrevious());
    }

    public function test_last_page_with_no_previous(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 10,
            current: 1
        );

        $this->assertFalse($pagination->hasNext());
        $this->assertFalse($pagination->hasPrevious());
    }

    // ===== Edge Cases =====

    public function test_single_page_pagination(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 10,
            current: 1,
            itemCount: 5
        );

        $this->assertSame(1, $pagination->total());
        $this->assertSame(1, $pagination->current());
        $this->assertFalse($pagination->hasNext());
        $this->assertFalse($pagination->hasPrevious());
        $this->assertCount(5, $pagination->items());
    }

    public function test_pagination_with_different_per_page_values(): void
    {
        $perPageValues = [5, 10, 20, 50, 100];

        foreach ($perPageValues as $perPage) {
            $pagination = $this->createPagination(2, 1, 10, $perPage, 1);
            $this->assertSame($perPage, $pagination->perPage());
        }
    }

    public function test_pagination_with_large_total_pages(): void
    {
        $pagination = $this->createPagination(
            next: 51,
            previous: 49,
            total: 100,
            perPage: 10,
            current: 50
        );

        $this->assertSame(100, $pagination->total());
        $this->assertSame(50, $pagination->current());
        $this->assertTrue($pagination->hasNext());
        $this->assertTrue($pagination->hasPrevious());
    }

    public function test_items_count_matches_data(): void
    {
        $itemCounts = [1, 5, 10, 25, 50];

        foreach ($itemCounts as $count) {
            $items = $this->generateItems($count);
            $pagination = new Pagination(
                next: 2,
                previous: 0,
                total: 3,
                perPage: $count,
                current: 1,
                data: collect($items)
            );

            $this->assertCount($count, $pagination->items());
        }
    }

    // ===== Data Integrity Tests =====

    public function test_items_preserve_order(): void
    {
        $items = ['first', 'second', 'third', 'fourth', 'fifth'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 5,
            current: 1,
            data: collect($items)
        );

        $this->assertSame($items, $pagination->items()->toArray());
    }

    public function test_items_with_associative_array(): void
    {
        $items = ['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 3,
            current: 1,
            data: collect($items)
        );

        $this->assertSame($items, $pagination->items()->toArray());
    }

    public function test_items_with_objects(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'Item 1'];
        $obj2 = (object)['id' => 2, 'name' => 'Item 2'];
        $items = [$obj1, $obj2];

        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 2,
            current: 1,
            data: collect($items)
        );

        $result = $pagination->items();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        
        // Verify objects are accessible via collection
        $this->assertSame($obj1, $result->first());
        $this->assertSame($obj2, $result->last());
    }

    // ===== Helper Methods =====

    private function createPagination(
        int $next,
        int $previous,
        int $total,
        int $perPage,
        int $current,
        int $itemCount = 3
    ): Pagination {
        return new Pagination(
            next: $next,
            previous: $previous,
            total: $total,
            perPage: $perPage,
            current: $current,
            data: collect($this->generateItems($itemCount))
        );
    }

    private function generateItems(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = "item{$i}";
        }
        return $items;
    }

    // ===== Data Providers =====

    public static function basicPaginationProvider(): array
    {
        return [
            'first page' => [2, 2, 1, 5, 10, 1],
            'middle page' => [3, 3, 1, 5, 10, 2],
            'last page' => [0, 0, 2, 3, 10, 3],
            'single page' => [0, 0, 0, 1, 10, 1],
            'page with different perPage' => [2, 2, 0, 10, 5, 1],
        ];
    }

    public static function navigationHelpersProvider(): array
    {
        return [
            'has next - next is not 0' => [true, 2],
            'no next - next is 0' => [false, 0],
            'has previous - previous is not 0' => [true, 1],
            'no previous - previous is 0' => [false, 0],
        ];
    }
}
