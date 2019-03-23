<?php

namespace Bow\Session\Driver;

use Bow\Database\Database as DB;
use Bow\Support\Capsule;

class DatabaseDriver implements \SessionHandlerInterface
{
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
     * Database constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->table = $options['table'] ?? 'sessions';
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
            ->where(['id' => $session_id])->delete();

        return true;
    }

    /**
     * Garbage collector for cleans old sessions
     *
     * @param int $maxlifetime
     * @return bool|void
     */
    public function gc($maxlifetime)
    {
        $this->sessions()
            ->where('time', '<', $this->createTimestamp(time() + $maxlifetime))
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
            return false;
        }

        return $session->data;
    }

    /**
     * Write session information
     *
     * @param string $session_id
     * @param string $session_data
     * @return bool|void
     */
    public function write($session_id, $session_data)
    {
        // When create the new session record
        if (! $this->sessions()->where('id', $session_id)->exists()) {
            return (bool) $this->sessions()->insert([
                'id' => $session_id,
                'time' => $this->createTimestamp((int) (config('session.lifetime') * 60)),
                'data' => '',
                'ip' => Capsule::getInstance()->make('request')->ip()
            ]);
        }

        // Update the session information
        $this->sessions()->where('id', $session_id)->update([
            'data' => $session_data,
            'id' => $session_id
        ]);

        return true;
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

    /**
     * Create the timestamp
     *
     * @return string
     */
    private function createTimestamp($time)
    {
        return date('Y-m-d H:i:s', time() + $time);
    }
}
