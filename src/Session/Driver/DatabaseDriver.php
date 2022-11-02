<?php

declare(strict_types=1);

namespace Bow\Session\Driver;

use Bow\Database\Database as DB;
use Bow\Support\Capsule;

class DatabaseDriver implements \SessionHandlerInterface
{
    use DurationTrait;

    /**
     * The session table name
     *
     * @var string
     */
    private $table;

    /**
     * The current session session_id
     *
     * @var string
     */
    private $session_id;

    /**
     * The current user ip
     *
     * @var string
     */
    private $ip;

    /**
     * Database constructor
     *
     * @param string $ip
     * @param array $options
     */
    public function __construct(array $options, $ip)
    {
        $this->table = $options['table'] ?? 'sessions';

        $this->ip = $ip;
    }

    /**
     * Close the session handling
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Destroy session information
     *
     * @param string $session_id
     * @return bool|void
     */
    public function destroy($session_id)
    {
        $this->sessions()
            ->where('id', $session_id)->delete();

        return true;
    }

    /**
     * Garbage collector for cleans old sessions
     *
     * @param int $max_lifetime
     * @return bool|void
     */
    public function gc($max_lifetime)
    {
        $this->sessions()
            ->where('time', '<', $this->createTimestamp())
            ->delete();

        return true;
    }

    /**
     * When the session start
     *
     * @param string $save_path
     * @param string $name
     * @return bool|void
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Read the session information
     *
     * @param string $session_id
     * @return string|void
     */
    public function read($session_id)
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
    public function write($session_id, $session_data)
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
     * @return array
     */
    private function data($session_id, $session_data)
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
     * @return \Bow\Database\QueryBuilder
     */
    private function sessions()
    {
        return DB::table($this->table);
    }
}
