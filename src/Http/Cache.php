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
     * Methode de configuration du cache
     *
     * @param string $base_directory
     */
    public static function confirgure($base_directory)
    {
        static::$directory = $base_directory;
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key The cache key
     * @param mixed $data
     */
    public static function add($key, $data)
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }
        if (is_array($content)) {
            $content['__bow_meta'] = ['is_array' => true, 'expire_at' => '+'];
        } else {
            $content = [$content];
            $content['__bow_meta'] = ['is_array' => false, 'expire_at' => '+'];
        }

        $content = serialize($content);
        file_put_contents(static::$directory.'/bow_'.$key, $content);
    }

    /**
     * Suppression d'entrer dans le cache.
     *
     * @param string $key
     */
    public static function remove($key)
    {
        @unlink(static::$directory.'/bow_'.$key);
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
            return null;
        }

        $content = unserialize(file_get_contents(static::$directory.'/bow_'.$key));
        $meta = $content['__bow_meta'];
        unset($content['__bow_meta']);

        if (! $meta['is_array']) {
            $content = $content[0];
        }

        return $content;
    }

    /**
     * Vérifier l'existance d'un entrée dans la cache.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return (bool) @file_exists(static::$directory.'/bow_'.$key);
    }

    /**
     * Vérifier l'existance d'un entrée dans la cache.
     *
     * @param string $key
     * @return bool
     */
    public static function expired($key)
    {
        return (bool) @file_exists(static::$directory.'/bow_'.$key);
    }
}