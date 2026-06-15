<?php

namespace Bow\Tests\Session;

use Bow\Database\Database;
use Bow\Session\Adapters\DatabaseAdapter;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;

class DatabaseAdapterTest extends TestCase
{
    private DatabaseAdapter $adapter;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);
        Database::connection('sqlite');
        Database::statement("DROP TABLE IF EXISTS sessions;");
        Database::statement("
            CREATE TABLE sessions (
                id varchar(200) not null primary key,
                time datetime null,
                data text null,
                ip varchar(255) null
            )");
    }

    protected function setUp(): void
    {
        Database::connection('sqlite');
        Database::statement("DELETE FROM sessions;");

        $this->adapter = new DatabaseAdapter(['table' => 'sessions'], '127.0.0.1');
    }

    /**
     * Seed a row directly with an explicit expiry offset so each test controls
     * exactly whether a session is expired or still active.
     */
    private function seed(string $id, string $data, int $expiresInSeconds): void
    {
        Database::table('sessions')->insert([
            'id' => $id,
            'time' => date('Y-m-d H:i:s', time() + $expiresInSeconds),
            'data' => $data,
            'ip' => '127.0.0.1',
        ]);
    }

    private function exists(string $id): bool
    {
        return Database::table('sessions')->where('id', $id)->exists();
    }

    /** Bug #1 — gc() must drop expired rows but keep still-valid ones. */
    public function test_gc_removes_only_expired_sessions(): void
    {
        $this->seed('expired-session', 'old', -60);   // expired a minute ago
        $this->seed('active-session', 'fresh', 3600); // valid for another hour

        $this->adapter->gc(0);

        $this->assertFalse($this->exists('expired-session'), 'Expired session should be collected');
        $this->assertTrue($this->exists('active-session'), 'Active session must survive gc');
    }

    /** Bug #3 — read() must not return data for an expired session. */
    public function test_read_returns_empty_for_expired_session(): void
    {
        $this->seed('stale', 'secret-payload', -60);

        $this->assertSame('', $this->adapter->read('stale'));
    }

    /** read() still returns the payload for a live session. */
    public function test_read_returns_data_for_active_session(): void
    {
        $this->seed('live', 'hello world', 3600);

        $this->assertSame('hello world', $this->adapter->read('live'));
    }

    /** Bug #2 — write() on an existing session must refresh its expiry (sliding window). */
    public function test_write_refreshes_expiry_on_update(): void
    {
        $this->adapter->write('rolling', 'v1');

        // Simulate time passing: push the stored expiry into the past.
        Database::table('sessions')
            ->where('id', 'rolling')
            ->update(['time' => date('Y-m-d H:i:s', time() - 60)]);

        $this->adapter->write('rolling', 'v2');

        $row = Database::table('sessions')->where('id', 'rolling')->first();

        $this->assertSame('v2', $row->data, 'Payload should be updated');
        $this->assertGreaterThan(
            date('Y-m-d H:i:s'),
            $row->time,
            'Expiry must be pushed back into the future on each write'
        );
    }
}
