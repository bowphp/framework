<?php
namespace Bow\Http;
use Bow\Application\Configuration;

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
        if (static::has($key)) {
            return unserialize(file_get_contents(static::$directory.'/bow_'.$key));
        }
        return null;
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
}