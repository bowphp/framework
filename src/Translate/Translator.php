<?php

namespace Bow\Translate;

use Bow\Support\Arraydotify;

class Translator
{
    /**
     * The define langue
     *
     * @var string
     */
    private static $lang;

    /**
     * The lang directory
     *
     * @var string
     */
    private static $directory;

    /**
     * The Translator instance
     *
     * @var Translator
     */
    private static $instance;

    /**
     * Translator constructor.
     *
     * @param string $lang
     * @param string $directory
     * @param bool $auto_detected
     */
    public function __construct($lang, $directory, $auto_detected = false)
    {
        static::$lang = $lang;

        if ($auto_detected) {
            static::$lang = strtolower(client_locale());

            if (is_null(static::$lang)) {
                static::$lang = $lang;
            }
        }

        static::$directory = $directory;
    }

    /**
     * Configure translator
     *
     * @param string $lang
     * @param string $directory
     *
     * @return Translator
     */
    public static function configure($lang, $directory)
    {
        if (static::$instance === null) {
            static::$instance = new self($lang, $directory);
        }

        return static::$instance;
    }

    /**
     * Get singleton instance
     *
     * @return Translator
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Check the locale
     *
     * @param string $locale
     *
     * @return bool
     */
    public static function isLocale($locale)
    {
        return static::$lang == $locale;
    }

    /**
     * Allows translation
     *
     * @param  string $key
     * @param  array  $data
     * @param  bool   $plurial
     *
     * @return string
     */
    public static function translate($key, array $data = [], $plurial = false)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException(
                'The first parameter must be a string.',
                E_USER_ERROR
            );
        }

        $map = explode('.', $key);

        if (count($map) == 1) {
            return $key;
        }

        // Formatage du path de fichier de la translation
        $translation_filename = static::$directory . '/' . static::$lang . '/' . current($map) . '.php';

        if (!file_exists($translation_filename)) {
            return $key;
        }

        $contents = require $translation_filename;

        if (!is_array($contents)) {
            return $key;
        }

        array_shift($map);

        $key = implode('.', $map);

        $translations = Arraydotify::make($contents);

        if (!isset($translations[$key])) {
            return $key;
        }

        $value = $translations[$key];
        $parts = explode('|', $value);

        if ($plurial === true) {
            if (!isset($parts[1])) {
                return $key;
            }

            $value = $parts[1];
        } else {
            $value = $parts[0];
        }

        return static::format($value, $data);
    }

    /**
     * Make singleton translation
     *
     * @param string $key
     * @param array $data
     *
     * @return string
     */
    public static function single($key, array $data = [])
    {
        return static::translate($key, $data);
    }

    /**
     * Make plurial translation
     *
     * @param $key
     * @param array $data
     * @return string
     */
    public static function plurial($key, array $data = [])
    {
        return static::translate($key, $data, true);
    }

    /**
     * Permet de formater
     *
     * @param  $str
     * @param  array $values
     * @return string
     */
    private static function format($str, array $values = [])
    {
        foreach ($values as $key => $value) {
            $str = preg_replace('/{\s*' . $key . '\s*\}/', $value, $str);
        }

        return $str;
    }

    /**
     * Update locale
     *
     * @param $locale
     */
    public static function setLocale($locale)
    {
        static::$lang = $locale;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public static function getLocale()
    {
        return static::$lang;
    }

    /**
     * __call
     *
     * @param  $name
     * @param  $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \BadMethodCallException('Undefined method ' . $name);
    }
}
