<?php

declare(strict_types=1);

namespace Bow\Session\Driver;

use Bow\Database\QueryBuilder;
use Bow\Database\Database as DB;

class DatabaseDriver implements \SessionHandlerInterface
{
    use DurationTrait;

    /**
     * The session table name
     *
     * @var string
     */
    private string $table;

    /**
     * The current session session_id
     *
     * @var string
     */
    private string $session_id;

    /**
     * The current user ip
     *
     * @var string
     */
    private string $ip;

    /**
     * Database constructor
     *
     * @param array $options
     * @param string $ip
     */
    public function __construct(array $options, string $ip)
    {
        $this->table = $options['table'] ?? 'sessions';

        $this->ip = $ip;
    }

    /**
     * Close the session handling
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy session information
     *
     * @param string $session_id
     * @return bool
     */
    public function destroy(string $session_id): bool
    {
        $this->sessions()
            ->where('id', $session_id)->delete();

        return true;
    }

    /**
     * Garbage collector for cleans old sessions
     *
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        $this->sessions()
            ->where('time', '<', $this->createTimestamp())
            ->delete();

        return 1;
    }

    /**
     * When the session start
     *
     * @param string $save_path
     * @param string $name
     * @return bool
     */
    public function open(string $save_path, string $name): bool
    {
        return true;
    }

    /**
     * Read the session information
     *
     * @param string $session_id
     * @return string
     */
    public function read(string $session_id): string
    {
        $session = $this->sessions()
            ->where('id', $session_id)->first();

        if (is_null($session)) {
            return '';
        }

        return $session->data;
    }

    /**
     * Write session information
     *
     * @param string $session_id
     * @param string $session_data
     * @return bool
     */
    public function write(string $session_id, string $session_data): bool
    {
        // When create the new session record
        if (! $this->sessions()->where('id', $session_id)->exists()) {
            $insert = $this->sessions()
                ->insert($this->data($session_id, $session_data));

            return (bool) $insert;
        }

        // Update the session information
        $update = $this->sessions()->where('id', $session_id)->update([
            'data' => $session_data,
            'id' => $session_id
        ]);

        return (bool) $update;
    }

    /**
     * Get the insert data
     *
     * @param string $session_id
     * @param string $session_data
     * @return array
     */
    private function data(string $session_id, string $session_data): array
    {
        return [
            'id' => $session_id,
            'time' => $this->createTimestamp(),
            'data' => $session_data,
            'ip' => $this->ip
        ];
    }

    /**
     * Get session QueryBuilder instance
     *
     * @return QueryBuilder
     */
    private function sessions(): QueryBuilder
    {
        return DB::table($this->table);
    }
}
