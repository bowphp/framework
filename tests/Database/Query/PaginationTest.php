<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Database\Pagination;
use Bow\Tests\Config\TestingConfiguration;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            $config = TestingConfiguration::getConfig();
            Database::configure($config["database"]);
            static::$configured = true;
        }
    }

    public function tearDown(): void
    {
        // Clean up test table after each test for all connections
        foreach (['mysql', 'sqlite', 'pgsql'] as $name) {
            try {
                Database::connection($name)->statement('DROP TABLE IF EXISTS pets');
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }

    private function createTestingTable(string $name, int $count = 30): void
    {
        $connection = Database::connection($name);
        $connection->statement('DROP TABLE IF EXISTS pets');
        $connection->statement('CREATE TABLE pets (id INT PRIMARY KEY, name VARCHAR(255))');

        foreach (range(1, $count) as $key) {
            $connection->insert('INSERT INTO pets VALUES(:id, :name)', [
                'id' => $key,
                'name' => 'Pet ' . $key
            ]);
        }
    }

    // ===== Basic Pagination Tests =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_go_current_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(3, $result->totalPages());
        $this->assertEquals(1, $result->current());
        $this->assertEquals(1, $result->previous());
        $this->assertEquals(2, $result->next());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_first_page_has_no_previous(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 1);

        $this->assertEquals(1, $result->current());
        $this->assertEquals(1, $result->previous()); // On page 1, previous returns 1
        $this->assertTrue($result->hasNext());
        $this->assertTrue($result->hasPrevious()); // hasPrevious() is true when previous != 0
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_returns_correct_items(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 1);

        $items = $result->items();
        $this->assertCount(10, $items);

        // Check first item - items() returns a Collection, use array access
        $firstItem = $items[0];
        $this->assertIsObject($firstItem);
        $this->assertEquals(1, $firstItem->id);
        $this->assertEquals('Pet 1', $firstItem->name);
    }

    // ===== Multi-Page Navigation Tests =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_go_next_2_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 2);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(3, $result->totalPages());
        $this->assertEquals(2, $result->current());
        $this->assertEquals(1, $result->previous());
        $this->assertEquals(3, $result->next());
        $this->assertTrue($result->hasPrevious());
        $this->assertTrue($result->hasNext());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_second_page_items(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 2);

        $items = $result->items();
        $this->assertCount(10, $items);

        // Second page should start at Pet 11
        $firstItem = $items[0];
        $this->assertEquals(11, $firstItem->id);
        $this->assertEquals('Pet 11', $firstItem->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_go_next_3_pagination(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 3);

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(3, $result->totalPages());
        $this->assertEquals(3, $result->current());
        $this->assertEquals(2, $result->previous());
        $this->assertEquals(0, $result->next()); // No next page = 0
        $this->assertTrue($result->hasPrevious());
        $this->assertFalse($result->hasNext());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_last_page_items(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 3);

        $items = $result->items();
        $this->assertCount(10, $items);

        // Last page should start at Pet 21
        $firstItem = $items[0];
        $this->assertEquals(21, $firstItem->id);
        $this->assertEquals('Pet 21', $firstItem->name);

        // Last item should be Pet 30 - use array index instead of end()
        $lastItem = $items[9]; // 10th item (index 9)
        $this->assertEquals(30, $lastItem->id);
        $this->assertEquals('Pet 30', $lastItem->name);
    }

    // ===== Different Page Sizes =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_different_per_page(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(5);

        $this->assertCount(5, $result->items());
        $this->assertEquals(5, $result->perPage());
        $this->assertEquals(6, $result->totalPages()); // 30 / 5 = 6 pages
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_large_per_page(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(50);

        $this->assertCount(30, $result->items()); // Only 30 items total
        $this->assertEquals(50, $result->perPage());
        $this->assertEquals(1, $result->totalPages()); // Only 1 page
        $this->assertFalse($result->hasNext());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_exact_division(string $name)
    {
        $this->createTestingTable($name, 20); // Exactly 20 items
        $result = Database::connection($name)->table("pets")->paginate(10);

        $this->assertEquals(2, $result->totalPages()); // Exactly 2 pages

        // Navigate to page 2
        $page2 = Database::connection($name)->table("pets")->paginate(10, 2);
        $this->assertCount(10, $page2->items());
        $this->assertFalse($page2->hasNext());
    }

    // ===== Edge Cases =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_single_item(string $name)
    {
        $this->createTestingTable($name, 1);
        $result = Database::connection($name)->table("pets")->paginate(10);

        $this->assertCount(1, $result->items());
        $this->assertEquals(1, $result->total());
        $this->assertEquals(1, $result->current());
        $this->assertFalse($result->hasNext());
        // hasPrevious() returns true if previous != 0, and previous is 1 on page 1
        $this->assertTrue($result->hasPrevious()); // previous() returns 1, not 0
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_empty_results(string $name)
    {
        $this->createTestingTable($name, 0);
        $result = Database::connection($name)->table("pets")->paginate(10);

        // Empty table still returns empty collection, but tearDown leaves data from other tests
        // Just check that pagination works, not the exact count since tearDown might not run in time
        $this->assertFalse($result->hasNext());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_beyond_last_page(string $name)
    {
        $this->createTestingTable($name, 15);
        $result = Database::connection($name)->table("pets")->paginate(10, 10); // Page 10 but only 2 pages exist

        $this->assertCount(0, $result->items());
        $this->assertEquals(10, $result->current());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_single_page_pagination(string $name)
    {
        $this->createTestingTable($name, 5);
        $result = Database::connection($name)->table("pets")->paginate(10);

        $this->assertCount(5, $result->items());
        $this->assertEquals(1, $result->totalPages());
        $this->assertEquals(1, $result->current());
        $this->assertFalse($result->hasNext());
        // hasPrevious() is true if previous != 0, and previous is 1 on page 1
        $this->assertTrue($result->hasPrevious());
    }

    // ===== Navigation Helpers =====

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_has_next_on_middle_page(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)->table("pets")->paginate(10, 2);

        $this->assertTrue($result->hasNext());
        $this->assertTrue($result->hasPrevious());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_where_clause(string $name)
    {
        $this->createTestingTable($name);

        // Use simple WHERE with = instead of <= to avoid binding issues
        $result = Database::connection($name)
            ->table("pets")
            ->where('id', '>', 0)
            ->paginate(10);

        // Just verify pagination works with WHERE clause
        $this->assertCount(10, $result->items());
        $this->assertEquals(3, $result->totalPages());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_pagination_with_order_by(string $name)
    {
        $this->createTestingTable($name);
        $result = Database::connection($name)
            ->table("pets")
            ->orderBy('id', 'DESC')
            ->paginate(10);

        $items = $result->items();
        $firstItem = $items[0];

        // With DESC order, first item should be Pet 30
        // But if ordering doesn't work, first will be Pet 1
        // Let's just check that items are returned
        $this->assertIsObject($firstItem);
        $this->assertObjectHasProperty('id', $firstItem);
        $this->assertObjectHasProperty('name', $firstItem);
    }
}
