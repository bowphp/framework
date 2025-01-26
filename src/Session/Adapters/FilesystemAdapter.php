<?php

declare(strict_types=1);

namespace Bow\Session\Adapters;

use SessionHandlerInterface;

class FilesystemAdapter implements SessionHandlerInterface
{
    use DurationTrait;

    /**
     * The session save path
     *
     * @var string
     */
    private string $save_path;

    /**
     * Filesystem constructor
     *
     * @param string $save_path
     */
    public function __construct(string $save_path)
    {
        $this->save_path = $save_path;
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
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $file = $this->sessionFile($id);

        @unlink($file);

        return true;
    }

    /**
     * Build the session file name
     *
     * @param string $session_id
     * @return string
     */
    private function sessionFile(string $session_id): string
    {
        return $this->save_path . '/' . basename($session_id);
    }

    /**
     * Garbage collector
     *
     * @param int $maxlifetime
     * @return int|false
     */
    public function gc(int $maxlifetime): int|false
    {
        foreach (glob($this->save_path . "/*") as $file) {
            if (filemtime($file) + $maxlifetime < $this->createTimestamp() && file_exists($file)) {
                @unlink($file);
            }
        }

        return 1;
    }

    /**
     * When the session start
     *
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        if (!is_dir($this->save_path)) {
            mkdir($this->save_path);
        }

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
        return (string)@file_get_contents($this->sessionFile($session_id));
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
        $saved = @file_put_contents($this->sessionFile($session_id), $session_data);

        return $saved !== false;
    }
}
