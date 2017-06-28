<?php
namespace Bow\Translate;

use Bow\Support\Arraydotify;

class Translator
{
    /**
     * @var string
     */
    private static $lang;

    /**
     * @var string
     */
    private static $directory;

    /**
     * @var Translator
     */
    private static $instance;

    /**
     * Translator constructor.
     *
     * @param string $lang
     * @param string $directory
     */
    public function __construct($lang, $directory)
    {
        static::$lang = $lang;
        static::$directory = $directory;
    }

    /**
     * @param string $lang
     * @param string $directory
     */
    public static function configure($lang, $directory)
    {
        if (static::$instance === null) {
            static::$instance = new self($lang, $directory);
        }
    }

    /**
     * @return Translator
     */
    public static function singleton()
    {
        return static::$instance;
    }

    /**
     * Permet de faire la tranduction
     *
     * @param string $key
     * @param array $data
     * @param bool $plurial
     * @return string
     */
    public static function make($key, array $data = [], $plurial = false)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('La premier parametre doit etre une chaine de carractère.', E_USER_ERROR);
        }

        $map = explode('.', $key);

        if (count($map) == 1) {
            return $key;
        }

        // Formatage du path de fichier de la translation
        $translation_filename = static::$directory.'/'.static::$lang.'/'.current($map).'.php';

        if (!file_exists($translation_filename)) {
            return $key;
        }

        array_shift($map);
        $key = implode('.', $map);

        $contents = require $translation_filename;
        $translations = Arraydotify::make($contents);

        if (!isset($translations[$key])) {
            return $key;
        }

        $value = $translations[$key];
        $parts = explode('|', $value);

        if ($plurial === true) {
            if (isset($parts[1])) {
                $value = $parts[1];
            } else {
                return $key;
            }
        } else {
            $value = $parts[0];
        }

        return static::format($value, $data);
    }

    /**
     * @param $key
     * @param array $data
     * @return string
     */
    public static function single($key, array $data = [])
    {
        return static::make($key, $data);
    }

    /**
     * @param $key
     * @param array $data
     * @return string
     */
    public static function pluiral($key, array $data = [])
    {
        return static::make($key, $data, true);
    }

    /**
     * Permet de formater
     *
     * @param $str
     * @param array $values
     * @return string
     */
    private static function format($str, array $values = [])
    {
        foreach ($values as $key => $value) {
            $str = preg_replace('/\{\{\s*'.$key.'\s*\}\}/', $value, $str);
        }

        return $str;
    }

    /**
     * __call
     *
     * @param $name
     * @param $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new \BadMethodCallException('undefined method '.$name);
    }
}