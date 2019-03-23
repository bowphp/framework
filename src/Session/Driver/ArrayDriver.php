<?php

namespace Bow\Session\Driver;

class ArrayDriver implements \SessionHandlerInterface
{
    /**
     * @var array
     */
    private $sessions;

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
         @unset($this->sessions[$session_id]);
    }

    /**
     * Garbage collector
     *
     * @param int $maxlifetime
     * @return bool|void
     */
    public function gc($maxlifetime)
    {
        foreach ($this->sessions as $session_id => $content) {
            if ($this->sessions[$session_id]['time'] <= $maxlifetime) {
                $this->destroy($session_id);
            }
        }
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
        $this->sessions[$session_id] = [
            'time' =>
        ];
    }

    /**
     * Read the session information
     *
     * @param string $session_id
     * @return string|void
     */
    public function read($session_id)
    {
        //
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
        $this->sessions[$session_id];
    }
}
