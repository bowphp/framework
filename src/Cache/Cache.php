<?php

declare(strict_types=1);

namespace Bow\Cache;

use BadMethodCallException;
use Bow\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Cache
{
    /**
     * The cache directory
     *
     * @var string
     */
    private static ?string $directory = null;

    /**
     * The meta data
     *
     * @var bool
     */
    private static bool $with_meta = false;

    /**
     * Cache constructor.
     *
     * @param string $base_directory
     * @return mixed
     */
    public function __construct(string $base_directory)
    {
        static::confirgure($base_directory);
    }

    /**
     * Cache configuration method
     *
     * @param string $base_directory
     */
    public static function confirgure(string $base_directory)
    {
        if (static::$directory === null || static::$directory !== $base_directory) {
            static::$directory = $base_directory;
        }

        if (!is_dir($base_directory)) {
            @mkdir($base_directory, 0777);
        }
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key
     * @param mixed $data
     * @param ?int $time
     * @return bool
     */
    public static function add(string $key, mixed $data, ?int $time = null): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_at' => $time == null ? '+' : $time];

        $meta['content'] = $content;

        return (bool) file_put_contents(
            static::makeHashFilename($key, true),
            serialize($meta)
        );
    }

    /**
     * Add many item
     *
     * @param array $data
     * @return bool
     */
    public static function addMany(array $data) : bool
    {
        $return = true;

        foreach ($data as $attribute => $value) {
            $return = static::add($attribute, $value);
        }

        return $return;
    }

    /**
     * Adds a cache that will persist
     *
     * @param  string $key  The cache key
     * @param  mixed  $data
     * @return bool
     */
    public static function forever(string $key, mixed $data): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_at' => '+'];

        $meta['content'] = $content;

        return (bool) file_put_contents(
            static::makeHashFilename($key, true),
            serialize($meta)
        );
    }

    /**
     * Add new enter in the cache system
     *
     * @param  string $key  The cache key
     * @param  mixed  $data
     * @return bool
     */
    public static function push(string $key, array $data): bool
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        static::$with_meta = true;

        $cache = static::get($key);

        static::$with_meta = false;

        if (is_array($cache['content'])) {
            array_push($cache['content'], $content);
        } else {
            $cache['content'] .= $content;
        }

        return (bool) file_put_contents(
            static::makeHashFilename($key),
            serialize($cache)
        );
    }

    /**
     * Retrieve an entry in the cache
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!static::has($key)) {
            static::$with_meta = false;

            if (is_callable($default)) {
                return $default();
            }
            
            return $default;
        }

        $cache = unserialize(file_get_contents(static::makeHashFilename($key)));

        if (!static::$with_meta) {
            unset($cache['__bow_meta']);

            $cache = $cache['content'];
        }

        return $cache;
    }

    /**
     * Increase the cache expiration time
     *
     * @param  string $key
     * @param  int    $time
     * @return bool
     */
    public static function addTime(string $key, int $time): bool
    {
        static::$with_meta = true;

        $cache = static::get($key);

        static::$with_meta = false;

        if ($cache == null) {
            return false;
        }

        if ($cache['__bow_meta']['expire_at'] == '+') {
            $cache['__bow_meta']['expire_at'] = time();
        }

        $cache['__bow_meta']['expire_at'] += $time;

        return (bool) file_put_contents(
            static::makeHashFilename($key),
            serialize($cache)
        );
    }

    /**
     * Retrieves the cache expiration time
     *
     * @param  string $key
     * @return int|bool|string
     */
    public static function timeOf(string $key): int|bool|string
    {
        static::$with_meta = true;

        $cache = static::get($key);

        static::$with_meta = false;

        if ($cache == null) {
            return false;
        }

        return $cache['__bow_meta']['expire_at'];
    }

    /**
     * Delete an entry in the cache
     *
     * @param  string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        $filename = static::makeHashFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        $result = (bool) @unlink($filename);

        $parts = explode('/', $filename);

        array_pop($parts);

        $dirname = implode('/', $parts);

        if (is_dir($dirname)) {
            @rmdir($dirname);
        }

        return $result;
    }

    /**
     * Check for an entry in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        $filename = static::makeHashFilename($key);

        return (bool) @file_exists($filename);
    }

    /**
     * Check if the cache has expired
     *
     * @param  string $key
     * @return bool
     */
    public static function expired(string $key): bool
    {
        static::$with_meta = true;

        $cache = static::get($key);

        if ($cache == null) {
            return false;
        }

        static::$with_meta = false;

        return $cache['__bow_meta']['expire_at'] == '+'
            ? false
            : (time() > $cache['__bow_meta']['expire_at']);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public static function clear(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(static::$directory),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! $file->isDir()) {
                unlink($file->getRealPath());
            }
        }
    }

    /**
     * Format the file
     *
     * @param  string $key
     * @param  bool  $make_group_directory
     * @return string
     */
    private static function makeHashFilename(string $key, bool $make_group_directory = false): string
    {
        $hash = hash('sha256', '/bow_'.$key);

        $group = Str::slice($hash, 0, 2);

        if ($make_group_directory) {
            if (!is_dir(static::$directory.'/'.$group)) {
                @mkdir(static::$directory.'/'.$group);
            }
        }

        return static::$directory.'/'.$group.'/'.$hash;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new BadMethodCallException("The $name method does not exist");
    }
}
