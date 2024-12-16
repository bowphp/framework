<?php

declare(strict_types=1);

namespace Bow\Session\Driver;

class ArrayDriver implements \SessionHandlerInterface
{
    use DurationTrait;

    /**
     * Define the data store
     *
     * @var array
     */
    private array $sessions = [];

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
     * @return bool|void
     */
    public function destroy(string $session_id): bool
    {
        return true;
    }

    /**
     * Garbage collector
     *
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        foreach ($this->sessions as $session_id => $content) {
            if ($this->sessions[$session_id]['time'] <= $this->createTimestamp()) {
                $this->destroy($session_id);
            }
        }

        return 1;
    }

    /**
     * When the session start
     *
     * @param string $save_path
     * @param string $session_id
     * @return bool
     */
    public function open(string $save_path, string $session_id): bool
    {
        $this->sessions = [];

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
        if (!isset($this->sessions[$session_id])) {
            return '';
        }

        return $this->sessions[$session_id]['data'];
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
        $this->sessions[$session_id] = [
            'time' => $this->createTimestamp(),
            'data' => $session_data
        ];

        return true;
    }
}
