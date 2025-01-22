<?php

declare(strict_types=1);

namespace Bow\Cache\Adapter;

use Bow\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FilesystemAdapter implements CacheAdapterInterface
{
    /**
     * The cache directory
     *
     * @var ?string
     */
    private ?string $directory = null;

    /**
     * The meta data
     *
     * @var bool
     */
    private bool $with_meta = false;

    /**
     * Cache constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->directory = $config["path"];

        if (!is_dir($this->directory)) {
            @mkdir($this->directory);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->add($key, $data, $time);
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $data, ?int $time = 60): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_at' => $time == null ? '+' : time() + $time];

        $meta['content'] = $content;

        return (bool)file_put_contents(
            $this->makeHashFilename($key, true),
            serialize($meta)
        );
    }

    private function makeHashFilename(string $key, bool $make_group_directory = false): string
    {
        $hash = hash('sha256', '/bow_' . $key);

        $group = Str::slice($hash, 0, 2);

        if ($make_group_directory) {
            if (!is_dir($this->directory . '/' . $group)) {
                @mkdir($this->directory . '/' . $group);
            }
        }

        return $this->directory . '/' . $group . '/' . $hash;
    }

    /**
     * @inheritDoc
     */
    public function addMany(array $data): bool
    {
        $return = true;

        foreach ($data as $attribute => $value) {
            $return = $this->add($attribute, $value);
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function forever(string $key, mixed $data): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_at' => '+'];

        $meta['content'] = $content;

        return (bool)file_put_contents(
            $this->makeHashFilename($key, true),
            serialize($meta)
        );
    }

    /**
     * @inheritDoc
     */
    public function push(string $key, array $data): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $this->with_meta = true;

        $cache = $this->get($key);

        $this->with_meta = false;

        if (is_array($cache['content'])) {
            $cache['content'][] = $content;
        } else {
            $cache['content'] .= $content;
        }

        return (bool)file_put_contents(
            $this->makeHashFilename($key),
            serialize($cache)
        );
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            $this->with_meta = false;

            if (is_callable($default)) {
                return $default();
            }

            return $default;
        }

        $cache = unserialize(file_get_contents($this->makeHashFilename($key)));

        $expire_at = $cache['__bow_meta']['expire_at'];

        if ($expire_at != '+') {
            if (time() > $expire_at) {
                return null;
            }
        }

        if (!$this->with_meta) {
            unset($cache['__bow_meta']);

            $cache = $cache['content'];
        }

        return $cache;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $filename = $this->makeHashFilename($key);

        return (bool)@file_exists($filename);
    }

    /**
     * @inheritDoc
     */
    public function addTime(string $key, int $time): bool
    {
        $this->with_meta = true;

        $cache = $this->get($key);

        $this->with_meta = false;

        if ($cache == null) {
            return false;
        }

        if ($cache['__bow_meta']['expire_at'] == '+') {
            $cache['__bow_meta']['expire_at'] = time() + $time;
        } else {
            $cache['__bow_meta']['expire_at'] += $time;
        }

        return (bool)file_put_contents(
            $this->makeHashFilename($key),
            serialize($cache)
        );
    }

    /**
     * @inheritDoc
     */
    public function timeOf(string $key): int|bool|string
    {
        $this->with_meta = true;

        $cache = $this->get($key);

        $this->with_meta = false;

        if ($cache == null) {
            return false;
        }

        return (int)$cache['__bow_meta']['expire_at'];
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        $filename = $this->makeHashFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        $result = (bool)@unlink($filename);
        $parts = explode('/', $filename);
        array_pop($parts);

        $dirname = implode('/', $parts);

        if (is_dir($dirname)) {
            @rmdir($dirname);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function expired(string $key): bool
    {
        $this->with_meta = true;

        $cache = $this->get($key);

        if ($cache == null) {
            return false;
        }

        $expire_at = $cache['__bow_meta']['expire_at'];

        $this->with_meta = false;

        return !($expire_at == '+') && time() > $expire_at;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                unlink($file->getRealPath());
            }
        }
    }
}
