<?php
namespace Bow\Http;

/**
 * Class Cache
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class Cache
{
    /**
     * @var string
     */
    private static $directory;

    /**
     * @var bool
     */
    private static $with_meta = false;

    /**
     * Methode de configuration du cache
     *
     * @param string $base_directory
     */
    public static function confirgure($base_directory)
    {
        static::$directory = $base_directory;
        if (! is_dir($base_directory)) {
            @mkdir($base_directory, 0777);
        }
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key The cache key
     * @param mixed $data
     * @param int $time
     * @return bool
     */
    public static function add($key, $data, $time = null)
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_to' => $time == null ? '+' : $time];
        $meta['content'] = $content;
        return (bool) file_put_contents(static::$directory.md5('/bow_'.$key), serialize($meta));
    }

    /**
     * Permet d'ajouter un cache qui persistera
     *
     * @param string $key The cache key
     * @param mixed $data
     * @return bool
     */
    public static function forever($key, $data)
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $meta['__bow_meta'] = ['expire_to' => '+'];
        $meta['content'] = $content;
        return (bool) file_put_contents(static::$directory.md5('/bow_'.$key), serialize($meta));
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key The cache key
     * @param mixed $data
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

        return (bool) file_put_contents(static::$directory.md5('/bow_'.$key), serialize($cache));
    }

    /**
     * Récupérer une entrée dans le cache
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        if (! static::has($key)) {
            static::$with_meta = false;
            return null;
        }

        $cache = unserialize(file_get_contents(static::$directory.md5('/bow_'.$key)));

        if (! static::$with_meta) {
            unset($cache['__bow_meta']);
            $cache = $cache['content'];
        }

        return $cache;
    }

    /**
     * Permet d'augmenter le temps d'expiration du cache
     *
     * @param string $key
     * @param int $time
     * @return bool
     */
    public static function addTime($key, $time)
    {
        static::$with_meta = true;
        $cache = static::get($key);
        if ($cache == null) {
            return false;
        }
        static::$with_meta = false;

        if ($cache['__bow_meta']['expire_at'] == '+') {
            $cache['__bow_meta']['expire_at'] = time();
        }

        $cache['__bow_meta']['expire_at'] += $time;
        return true;
    }

    /**
     * Permet de supprimer une entrer dans le cache
     *
     * @param string $key
     * @return bool
     */
    public static function forget($key)
    {
        return (bool) @unlink(static::$directory.md5('/bow_'.$key));
    }

    /**
     * Vérifier l'existance d'un entrée dans la cache.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        $filename = static::$directory.md5('/bow_'.$key);
        return (bool) @file_exists($filename);
    }

    /**
     * Permet de vérifier si le cache a expiré
     *
     * @param string $key
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

        return $cache['__bow_meta']['expire_at'] == '+' ? false : (time() > $cache['__bow_meta']['expire_at']);
    }
}