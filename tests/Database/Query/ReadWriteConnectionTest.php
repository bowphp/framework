<?php

namespace Bow\Tests\Database\Query;

use Bow\Database\Connection\Adapters\SqliteAdapter;
use Bow\Database\Database;
use Bow\Database\QueryBuilder;
use Bow\Tests\Config\TestingConfiguration;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Functional coverage for read/write connection splitting.
 *
 * Two distinct SQLite files are used as the "write" (primary) and "read"
 * (replica) databases. Because the two files hold different rows, the row a
 * query returns reveals which connection actually served it.
 */
class ReadWriteConnectionTest extends TestCase
{
    private static string $write_db;
    private static string $read_db;

    public static function setUpBeforeClass(): void
    {
        // Boot the app config so QueryBuilder's query event can be dispatched.
        Database::configure(TestingConfiguration::getConfig()["database"]);

        self::$write_db = tempnam(sys_get_temp_dir(), 'bow_write_');
        self::$read_db = tempnam(sys_get_temp_dir(), 'bow_read_');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$write_db);
        @unlink(self::$read_db);
    }

    /**
     * Build a split adapter and seed both files with a distinguishable row.
     */
    private function makeSplitAdapter(): SqliteAdapter
    {
        $adapter = new SqliteAdapter([
            'driver' => 'sqlite',
            'database' => self::$write_db,
            'read' => [
                'database' => self::$read_db,
            ],
        ]);

        $this->seed($adapter->getWriteConnection(), 'primary');
        $this->seed($adapter->getReadConnection(), 'replica');

        return $adapter;
    }

    private function seed(PDO $pdo, string $marker): void
    {
        $pdo->exec('DROP TABLE IF EXISTS pets');
        $pdo->exec('CREATE TABLE pets (id INTEGER PRIMARY KEY, name VARCHAR(255))');
        $pdo->exec("INSERT INTO pets (id, name) VALUES (1, '" . $marker . "')");
    }

    public function test_split_config_builds_distinct_connections(): void
    {
        $adapter = $this->makeSplitAdapter();

        $this->assertNotSame(
            $adapter->getWriteConnection(),
            $adapter->getReadConnection(),
            'A split connection must expose two distinct PDO instances.'
        );
    }

    public function test_connections_are_opened_lazily(): void
    {
        $adapter = new SqliteAdapter([
            'driver' => 'sqlite',
            'database' => self::$write_db,
            'read' => ['database' => self::$read_db],
        ]);

        $this->assertFalse(
            $adapter->hasWriteConnection(),
            'No PDO should be opened until the connection is first used.'
        );

        // Touching only the read side must not open the write connection.
        $adapter->getReadConnection();
        $this->assertFalse(
            $adapter->hasWriteConnection(),
            'A read-only access must not open the primary connection.'
        );

        $adapter->getWriteConnection();
        $this->assertTrue($adapter->hasWriteConnection());
    }

    public function test_read_falls_back_to_write_without_read_config(): void
    {
        $adapter = new SqliteAdapter([
            'driver' => 'sqlite',
            'database' => self::$write_db,
        ]);

        $this->assertSame(
            $adapter->getWriteConnection(),
            $adapter->getReadConnection(),
            'Without a read block, reads must reuse the write connection.'
        );
    }

    public function test_select_routes_to_read_replica(): void
    {
        $adapter = $this->makeSplitAdapter();
        $builder = new QueryBuilder('pets', $adapter);

        $row = $builder->where('id', 1)->first();

        $this->assertSame('replica', $row->name);
    }

    public function test_write_routes_to_primary(): void
    {
        $adapter = $this->makeSplitAdapter();

        (new QueryBuilder('pets', $adapter))
            ->where('id', 1)
            ->update(['name' => 'updated']);

        // The write landed on the primary file...
        $primary = $adapter->getWriteConnection()
            ->query('SELECT name FROM pets WHERE id = 1')
            ->fetchColumn();
        $this->assertSame('updated', $primary);

        // ...and the replica file is untouched.
        $replica = $adapter->getReadConnection()
            ->query('SELECT name FROM pets WHERE id = 1')
            ->fetchColumn();
        $this->assertSame('replica', $replica);
    }

    public function test_reads_route_to_primary_during_transaction(): void
    {
        $adapter = $this->makeSplitAdapter();

        // Open a transaction on the primary; reads must now stick to it.
        $adapter->getWriteConnection()->beginTransaction();

        try {
            $row = (new QueryBuilder('pets', $adapter))->where('id', 1)->first();

            $this->assertSame(
                'primary',
                $row->name,
                'While a transaction is open, reads must hit the primary.'
            );
        } finally {
            $adapter->getWriteConnection()->rollBack();
        }

        // After the transaction closes, reads resume on the replica.
        $row = (new QueryBuilder('pets', $adapter))->where('id', 1)->first();
        $this->assertSame('replica', $row->name);
    }
}
