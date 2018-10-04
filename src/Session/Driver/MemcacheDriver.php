<?php

namespace Bow\Session\Driver;

class MemcacheDriver implements \SessionHandlerInterface
{
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
        // TODO: Implement destroy() method.
    }

    /**
     * Garbage collector
     *
     * @param int $maxlifetime
     * @return bool|void
     */
    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
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
        // TODO: Implement open() method.
    }

    /**
     * Read the session information
     *
     * @param string $session_id
     * @return string|void
     */
    public function read($session_id)
    {
        // TODO: Implement read() method.
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
        // TODO: Implement write() method.
    }
}
