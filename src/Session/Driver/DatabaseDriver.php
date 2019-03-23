<?php

namespace Bow\Session\Driver;

use Bow\Database\Database as DB;
use Bow\Support\Capsule;

class DatabaseDriver implements \SessionHandlerInterface
{
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
    }

    /**
     * Garbage collector
     *
     * @param int $maxlifetime
     * @return bool|void
     */
    public function gc($maxlifetime)
    {
        $this->sessions()->where(['time' => $maxlifetime]);
    }

    /**
     * When the session start
     *
     * @param string $save_path
     * @param string $session_id
     * @return bool|void
     */
    public function open($save_path, $session_id)
    {
        $session = $this->sessions()
            ->where('id', $session_id)->first();

        if (!is_null($session)) {
            return false;
        }

        $this->sessions()->insert([
            'id' => $session_id,
            'time' => time() + (int) (config('session.lifetime') * 60),
            'data' => null,
            'ip' => Capsule::getInstance()->make('request')->ip()
        ]);
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
        $this->sessions()->update([
            'data' => $session_data,
            'id' => $session_id
        ]);
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
