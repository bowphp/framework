<?php

declare(strict_types=1);

namespace Bow\Tests\Database\Query;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\SoftDeletePetModelStub;
use PHPUnit\Framework\TestCase;

class SoftDeleteTest extends TestCase
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
        foreach (['mysql', 'sqlite', 'pgsql'] as $name) {
            try {
                Database::connection($name)->statement('DROP TABLE IF EXISTS pets');
            } catch (\Exception $e) {
                // ignore
            }
        }
        parent::tearDown();
    }

    public function connectionNameProvider(): array
    {
        return [['mysql'], ['sqlite'], ['pgsql']];
    }

    private function createTestingTable(string $name): void
    {
        $connection = Database::connection($name);

        $sql = match ($name) {
            'pgsql'  => 'CREATE TABLE pets (id SERIAL PRIMARY KEY, name VARCHAR(255), deleted_at TIMESTAMP NULL)',
            'sqlite' => 'CREATE TABLE pets (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), deleted_at TIMESTAMP NULL)',
            'mysql'  => 'CREATE TABLE pets (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255), deleted_at TIMESTAMP NULL)',
            default  => throw new \InvalidArgumentException("Unsupported database: $name"),
        };

        $connection->statement('DROP TABLE IF EXISTS pets');
        $connection->statement($sql);
        $connection->insert('INSERT INTO pets(name) VALUES(:name)', [
            ['name' => 'Milou'],
            ['name' => 'Couli'],
            ['name' => 'Bobi'],
        ]);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_delete_writes_deleted_at_instead_of_removing_row(string $name): void
    {
        $this->createTestingTable($name);

        $pet = SoftDeletePetModelStub::withTrashed()->where('name', 'Milou')->first();
        $this->assertNotNull($pet);

        $affected = $pet->delete();
        $this->assertSame(1, $affected);

        // Row is still in the table but marked as deleted
        $total = (int) Database::connection($name)
            ->select('SELECT COUNT(*) AS n FROM pets')[0]->n;
        $this->assertSame(3, $total);

        $reloaded = SoftDeletePetModelStub::withTrashed()->where('name', 'Milou')->first();
        $this->assertNotNull($reloaded->deleted_at);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_trashed_reports_state(string $name): void
    {
        $this->createTestingTable($name);

        $pet = SoftDeletePetModelStub::withTrashed()->where('name', 'Couli')->first();
        $this->assertFalse($pet->trashed());

        $pet->delete();

        $this->assertTrue($pet->trashed());
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_withoutTrashed_excludes_soft_deleted(string $name): void
    {
        $this->createTestingTable($name);

        SoftDeletePetModelStub::withTrashed()->where('name', 'Bobi')->first()->delete();

        $active = SoftDeletePetModelStub::withoutTrashed()->get();

        $this->assertCount(2, $active);
        foreach ($active as $row) {
            $this->assertNotEquals('Bobi', $row->name);
        }
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_onlyTrashed_returns_only_soft_deleted(string $name): void
    {
        $this->createTestingTable($name);

        SoftDeletePetModelStub::withTrashed()->where('name', 'Milou')->first()->delete();

        $trashed = SoftDeletePetModelStub::onlyTrashed()->get();

        $this->assertCount(1, $trashed);
        $this->assertSame('Milou', $trashed->first()->name);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_restore_clears_deleted_at(string $name): void
    {
        $this->createTestingTable($name);

        $pet = SoftDeletePetModelStub::withTrashed()->where('name', 'Couli')->first();
        $pet->delete();
        $this->assertTrue($pet->trashed());

        $restored = $pet->restore();
        $this->assertTrue($restored);
        $this->assertFalse($pet->trashed());

        // Confirms the row also reads back as un-trashed from the DB
        $reloaded = SoftDeletePetModelStub::withTrashed()->where('name', 'Couli')->first();
        $this->assertNull($reloaded->deleted_at);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_forceDelete_removes_row_physically(string $name): void
    {
        $this->createTestingTable($name);

        $pet = SoftDeletePetModelStub::withTrashed()->where('name', 'Bobi')->first();

        $affected = $pet->forceDelete();
        $this->assertSame(1, $affected);

        $total = (int) Database::connection($name)
            ->select('SELECT COUNT(*) AS n FROM pets')[0]->n;
        $this->assertSame(2, $total);
    }

    /**
     * @dataProvider connectionNameProvider
     */
    public function test_withTrashed_returns_all_rows(string $name): void
    {
        $this->createTestingTable($name);

        SoftDeletePetModelStub::withTrashed()->where('name', 'Milou')->first()->delete();

        $all = SoftDeletePetModelStub::withTrashed()->get();

        $this->assertCount(3, $all); // active + trashed
    }
}
