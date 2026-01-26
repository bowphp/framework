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

    public function test_total_pages(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1
        );

        $this->assertSame(10, $pagination->totalPages());
    }

    public function test_total_pages_with_remainder(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 95,
            perPage: 10,
            current: 1
        );

        $this->assertSame(10, $pagination->totalPages());
    }

    public function test_has_pages_returns_true_when_multiple_pages(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1
        );

        $this->assertTrue($pagination->hasPages());
    }

    public function test_has_pages_returns_false_when_single_page(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 0,
            total: 5,
            perPage: 10,
            current: 1
        );

        $this->assertFalse($pagination->hasPages());
    }

    public function test_on_first_page(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1
        );

        $this->assertTrue($pagination->onFirstPage());
    }

    public function test_not_on_first_page(): void
    {
        $pagination = $this->createPagination(
            next: 3,
            previous: 1,
            total: 100,
            perPage: 10,
            current: 2
        );

        $this->assertFalse($pagination->onFirstPage());
    }

    public function test_on_last_page(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 9,
            total: 100,
            perPage: 10,
            current: 10
        );

        $this->assertTrue($pagination->onLastPage());
    }

    public function test_not_on_last_page(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1
        );

        $this->assertFalse($pagination->onLastPage());
    }

    public function test_is_empty(): void
    {
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 0,
            perPage: 10,
            current: 1,
            data: collect([])
        );

        $this->assertTrue($pagination->isEmpty());
    }

    public function test_is_not_empty(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1
        );

        $this->assertTrue($pagination->isNotEmpty());
    }

    public function test_count(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1,
            itemCount: 10
        );

        $this->assertSame(10, $pagination->count());
    }

    public function test_first_item(): void
    {
        $pagination = $this->createPagination(
            next: 3,
            previous: 1,
            total: 100,
            perPage: 10,
            current: 2,
            itemCount: 10
        );

        $this->assertSame(11, $pagination->firstItem());
    }

    public function test_first_item_on_first_page(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1,
            itemCount: 10
        );

        $this->assertSame(1, $pagination->firstItem());
    }

    public function test_first_item_with_empty_results(): void
    {
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 0,
            perPage: 10,
            current: 1,
            data: collect([])
        );

        $this->assertSame(0, $pagination->firstItem());
    }

    public function test_last_item(): void
    {
        $pagination = $this->createPagination(
            next: 3,
            previous: 1,
            total: 100,
            perPage: 10,
            current: 2,
            itemCount: 10
        );

        $this->assertSame(20, $pagination->lastItem());
    }

    public function test_last_item_on_last_page_with_remainder(): void
    {
        $pagination = $this->createPagination(
            next: 0,
            previous: 9,
            total: 95,
            perPage: 10,
            current: 10,
            itemCount: 5
        );

        $this->assertSame(95, $pagination->lastItem());
    }

    public function test_last_item_with_empty_results(): void
    {
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 0,
            perPage: 10,
            current: 1,
            data: collect([])
        );

        $this->assertSame(0, $pagination->lastItem());
    }

    public function test_to_array(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 30,
            perPage: 10,
            current: 1,
            itemCount: 10
        );

        $array = $pagination->toArray();

        $this->assertIsArray($array);
        $this->assertSame(1, $array['current_page']);
        $this->assertSame(10, $array['per_page']);
        $this->assertSame(30, $array['total']);
        $this->assertSame(3, $array['total_pages']);
        $this->assertSame(1, $array['first_item']);
        $this->assertSame(10, $array['last_item']);
        $this->assertSame(2, $array['next_page']);
        $this->assertNull($array['previous_page']);
        $this->assertIsArray($array['data']);
    }

    public function test_to_json(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 30,
            perPage: 10,
            current: 1,
            itemCount: 3
        );

        $json = $pagination->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame(1, $decoded['current_page']);
        $this->assertSame(30, $decoded['total']);
    }

    // ===== URL Support Tests =====

    public function test_set_and_get_base_url(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $pagination->setBaseUrl('https://example.com/items');

        $this->assertSame('https://example.com/items', $pagination->getBaseUrl());
    }

    public function test_set_base_url_returns_self(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $result = $pagination->setBaseUrl('https://example.com/items');

        $this->assertSame($pagination, $result);
    }

    public function test_set_and_get_page_param(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $pagination->setPageParam('p');

        $this->assertSame('p', $pagination->getPageParam());
    }

    public function test_default_page_param(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $this->assertSame('page', $pagination->getPageParam());
    }

    public function test_with_query_params(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $pagination->withQueryParams(['sort' => 'name']);
        $pagination->withQueryParams(['order' => 'asc']);

        $params = $pagination->getQueryParams();
        $this->assertSame(['sort' => 'name', 'order' => 'asc'], $params);
    }

    public function test_set_query_params_replaces_existing(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $pagination->withQueryParams(['sort' => 'name']);
        $pagination->setQueryParams(['filter' => 'active']);

        $params = $pagination->getQueryParams();
        $this->assertSame(['filter' => 'active'], $params);
    }

    public function test_url_returns_null_without_base_url(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);

        $this->assertNull($pagination->url(1));
    }

    public function test_url_builds_correct_url(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $url = $pagination->url(2);

        $this->assertSame('https://example.com/items?page=2', $url);
    }

    public function test_url_with_custom_page_param(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');
        $pagination->setPageParam('p');

        $url = $pagination->url(2);

        $this->assertSame('https://example.com/items?p=2', $url);
    }

    public function test_url_with_query_params(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');
        $pagination->withQueryParams(['sort' => 'name', 'order' => 'asc']);

        $url = $pagination->url(2);

        $this->assertStringContainsString('page=2', $url);
        $this->assertStringContainsString('sort=name', $url);
        $this->assertStringContainsString('order=asc', $url);
    }

    public function test_url_with_existing_query_string(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items?filter=active');

        $url = $pagination->url(2);

        $this->assertSame('https://example.com/items?filter=active&page=2', $url);
    }

    public function test_url_returns_null_for_invalid_page(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $this->assertNull($pagination->url(0));
        $this->assertNull($pagination->url(-1));
        $this->assertNull($pagination->url(11)); // total pages is 10
    }

    public function test_next_page_url(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $url = $pagination->nextPageUrl();

        $this->assertSame('https://example.com/items?page=2', $url);
    }

    public function test_next_page_url_returns_null_on_last_page(): void
    {
        $pagination = $this->createPagination(0, 9, 100, 10, 10);
        $pagination->setBaseUrl('https://example.com/items');

        $this->assertNull($pagination->nextPageUrl());
    }

    public function test_previous_page_url(): void
    {
        $pagination = $this->createPagination(3, 1, 100, 10, 2);
        $pagination->setBaseUrl('https://example.com/items');

        $url = $pagination->previousPageUrl();

        $this->assertSame('https://example.com/items?page=1', $url);
    }

    public function test_previous_page_url_returns_null_on_first_page(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $this->assertNull($pagination->previousPageUrl());
    }

    public function test_first_page_url(): void
    {
        $pagination = $this->createPagination(3, 1, 100, 10, 2);
        $pagination->setBaseUrl('https://example.com/items');

        $url = $pagination->firstPageUrl();

        $this->assertSame('https://example.com/items?page=1', $url);
    }

    public function test_last_page_url(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $url = $pagination->lastPageUrl();

        $this->assertSame('https://example.com/items?page=10', $url);
    }

    public function test_get_url_range(): void
    {
        $pagination = $this->createPagination(6, 4, 100, 10, 5);
        $pagination->setBaseUrl('https://example.com/items');

        $urls = $pagination->getUrlRange(2);

        $this->assertCount(5, $urls);
        $this->assertArrayHasKey(3, $urls);
        $this->assertArrayHasKey(4, $urls);
        $this->assertArrayHasKey(5, $urls);
        $this->assertArrayHasKey(6, $urls);
        $this->assertArrayHasKey(7, $urls);
        $this->assertSame('https://example.com/items?page=5', $urls[5]);
    }

    public function test_get_url_range_at_start(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $urls = $pagination->getUrlRange(3);

        $this->assertArrayHasKey(1, $urls);
        $this->assertArrayHasKey(2, $urls);
        $this->assertArrayHasKey(3, $urls);
        $this->assertArrayHasKey(4, $urls);
        $this->assertArrayNotHasKey(0, $urls);
    }

    public function test_get_url_range_at_end(): void
    {
        $pagination = $this->createPagination(0, 9, 100, 10, 10);
        $pagination->setBaseUrl('https://example.com/items');

        $urls = $pagination->getUrlRange(3);

        $this->assertArrayHasKey(7, $urls);
        $this->assertArrayHasKey(8, $urls);
        $this->assertArrayHasKey(9, $urls);
        $this->assertArrayHasKey(10, $urls);
        $this->assertArrayNotHasKey(11, $urls);
    }

    public function test_links(): void
    {
        $pagination = $this->createPagination(6, 4, 100, 10, 5);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(2);

        $this->assertIsArray($links);

        // First link should be "Previous"
        $this->assertSame('&laquo; Previous', $links[0]['label']);
        $this->assertFalse($links[0]['disabled']);

        // Last link should be "Next"
        $lastIndex = count($links) - 1;
        $this->assertSame('Next &raquo;', $links[$lastIndex]['label']);
        $this->assertFalse($links[$lastIndex]['disabled']);
    }

    public function test_links_with_current_page_marked_active(): void
    {
        $pagination = $this->createPagination(6, 4, 100, 10, 5);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(2);

        $activePage = array_filter($links, fn($link) => $link['active'] === true);
        $this->assertCount(1, $activePage);
        $activeLink = array_values($activePage)[0];
        $this->assertSame('5', $activeLink['label']);
    }

    public function test_links_on_first_page_has_disabled_previous(): void
    {
        $pagination = $this->createPagination(2, 0, 100, 10, 1);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(2);

        $this->assertTrue($links[0]['disabled']);
        $this->assertNull($links[0]['url']);
    }

    public function test_links_on_last_page_has_disabled_next(): void
    {
        $pagination = $this->createPagination(0, 9, 100, 10, 10);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(2);

        $lastIndex = count($links) - 1;
        $this->assertTrue($links[$lastIndex]['disabled']);
        $this->assertNull($links[$lastIndex]['url']);
    }

    public function test_links_includes_ellipsis_when_needed(): void
    {
        $pagination = $this->createPagination(6, 4, 100, 10, 5);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(1);

        $ellipsisLinks = array_filter($links, fn($link) => $link['label'] === '...');
        $this->assertGreaterThan(0, count($ellipsisLinks));
    }

    public function test_links_includes_first_and_last_page(): void
    {
        $pagination = $this->createPagination(6, 4, 100, 10, 5);
        $pagination->setBaseUrl('https://example.com/items');

        $links = $pagination->links(1);

        $labels = array_column($links, 'label');
        $this->assertContains('1', $labels);
        $this->assertContains('10', $labels);
    }

    // ===== ArrayAccess Tests =====

    public function test_offset_exists_returns_true_for_existing_key(): void
    {
        $items = ['a' => 'apple', 'b' => 'banana'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 2,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $this->assertTrue(isset($pagination['a']));
        $this->assertTrue(isset($pagination['b']));
    }

    public function test_offset_exists_returns_false_for_non_existing_key(): void
    {
        $items = ['a' => 'apple'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $this->assertFalse(isset($pagination['z']));
    }

    public function test_offset_get_returns_value(): void
    {
        $items = ['first', 'second', 'third'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 3,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $this->assertSame('first', $pagination[0]);
        $this->assertSame('second', $pagination[1]);
        $this->assertSame('third', $pagination[2]);
    }

    public function test_offset_get_with_associative_keys(): void
    {
        $items = ['name' => 'John', 'age' => 30];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 2,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $this->assertSame('John', $pagination['name']);
        $this->assertSame(30, $pagination['age']);
    }

    public function test_offset_set_modifies_value(): void
    {
        $items = ['a' => 'apple'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $pagination['a'] = 'avocado';

        $this->assertSame('avocado', $pagination['a']);
    }

    public function test_offset_set_adds_new_value(): void
    {
        $items = ['a' => 'apple'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 1,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $pagination['b'] = 'banana';

        $this->assertSame('banana', $pagination['b']);
    }

    public function test_offset_unset_removes_value(): void
    {
        $items = ['a' => 'apple', 'b' => 'banana'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 2,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        unset($pagination['a']);

        $this->assertFalse(isset($pagination['a']));
        $this->assertTrue(isset($pagination['b']));
    }

    // ===== IteratorAggregate Tests =====

    public function test_pagination_is_iterable(): void
    {
        $items = ['first', 'second', 'third'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 3,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $result = [];
        foreach ($pagination as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([0 => 'first', 1 => 'second', 2 => 'third'], $result);
    }

    public function test_pagination_iteration_with_associative_array(): void
    {
        $items = ['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 3,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $result = [];
        foreach ($pagination as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame($items, $result);
    }

    public function test_pagination_can_be_used_with_iterator_functions(): void
    {
        $items = [1, 2, 3, 4, 5];
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 5,
            perPage: 10,
            current: 1,
            data: collect($items)
        );

        $sum = 0;
        foreach ($pagination as $item) {
            $sum += $item;
        }

        $this->assertSame(15, $sum);
    }

    public function test_count_function_works_on_pagination(): void
    {
        $pagination = $this->createPagination(
            next: 2,
            previous: 0,
            total: 100,
            perPage: 10,
            current: 1,
            itemCount: 10
        );

        $this->assertCount(10, $pagination);
    }

    public function test_count_with_empty_pagination(): void
    {
        $pagination = new Pagination(
            next: 0,
            previous: 0,
            total: 0,
            perPage: 10,
            current: 1,
            data: collect([])
        );

        $this->assertCount(0, $pagination);
    }
}
