<?php

namespace Bow\Session\Driver;

class FilesystemDriver implements \SessionHandlerInterface
{
    use DurationTrait;

    /**
     * The session save path
     *
     * @var string
     */
    private $save_path;

    /**
     * Filesystem constructor
     *
     * @param string $save_path
     */
    public function __construct($save_path)
    {
        $this->save_path = $save_path;
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
        $file = $this->sessionFile($session_id);

        @unlink($file);

        return true;
    }

    /**
     * Garbage collector
     *
     * @param int $maxlifetime
     * @return bool|void
     */
    public function gc($maxlifetime)
    {
        foreach (glob($this->save_path . "/*") as $file) {
            if (filemtime($file) + $maxlifetime < $this->createTimestamp() && file_exists($file)) {
                @unlink($file);
            }
        }

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
        if (!is_dir($this->save_path)) {
            mkdir($this->save_path, 0777);
        }

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
        return (string) @file_get_contents($this->sessionFile($session_id));
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
        $saved = @file_put_contents($this->sessionFile($session_id), $session_data);

        return $saved !== false;
    }

    /**
     * Build the session file name
     *
     * @param string $session_id
     * @return string
     */
    private function sessionFile($session_id)
    {
        return $this->save_path . '/' . basename($session_id);
    }
}
