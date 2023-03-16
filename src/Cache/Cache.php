<?php

namespace Bow\Cache;

use BadMethodCallException;
use Bow\Support\Str;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Cache
{
    /**
     * The cache directory
     *
     * @var string
     */
    private static $directory;

    /**
     * The meta data
     *
     * @var bool
     */
    private static $with_meta = false;

    /**
     * Cache constructor.
     *
     * @param string $base_directory
     * @return mixed
     */
    public function __construct($base_directory)
    {
        static::confirgure($base_directory);
    }

    /**
     * Cache configuration method
     *
     * @param string $base_directory
     */
    public static function confirgure($base_directory)
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
     * @param int $time
     *
     * @return bool
     */
    public static function add($key, $data, $time = null)
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
    public static function addMany(array $data): bool
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
    public static function forever($key, $data)
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
    public static function push($key, $data)
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
    public static function get($key, $default = null)
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
    public static function addTime($key, $time)
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
     * @return bool|string|int
     */
    public static function timeOf($key)
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
    public static function forget($key)
    {
        $filename = static::makeHashFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        $r = (bool) @unlink($filename);

        $parts = explode('/', $filename);

        array_pop($parts);

        $dirname = implode('/', $parts);

        if (is_dir($dirname)) {
            @rmdir($dirname);
        }

        return $r;
    }

    /**
     * Check for an entry in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public static function has($key)
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
    public static function expired($key)
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
    public static function clear()
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
     * @param  bool   $make_group_directory
     * @return string
     */
    private static function makeHashFilename($key, $make_group_directory = false)
    {
        $hash = hash('sha256', '/bow_' . $key);

        $group = Str::slice($hash, 0, 2);

        if ($make_group_directory) {
            if (!is_dir(static::$directory . '/' . $group)) {
                @mkdir(static::$directory . '/' . $group);
            }
        }

        return static::$directory . '/' . $group . '/' . $hash;
    }

    /**
     * __call
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new BadMethodCallException("The $name method does not exist");
    }
}
