<?php

declare(strict_types=1);

namespace Bow\Session\Driver;

use SessionHandlerInterface;

class ArrayDriver implements SessionHandlerInterface
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
     * Garbage collector
     *
     * @param int $max_lifetime
     * @return int|false
     */
    public function gc(int $max_lifetime): int|false
    {
        foreach ($this->sessions as $id => $content) {
            if ($this->sessions[$id]['time'] <= $this->createTimestamp()) {
                $this->destroy($id);
            }
        }

        return 1;
    }

    /**
     * Destroy session information
     *
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        unset($this->sessions[$id]);

        return true;
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
        $this->sessions = [];

        return true;
    }

    /**
     * Read the session information
     *
     * @param string $id
     * @return string
     */
    public function read(string $id): string
    {
        if (!isset($this->sessions[$id])) {
            return '';
        }

        return $this->sessions[$id]['data'];
    }

    /**
     * Write session information
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $this->sessions[$id] = [
            'time' => $this->createTimestamp(),
            'data' => $data
        ];

        return true;
    }
}
