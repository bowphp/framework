<?php

declare(strict_types=1);

namespace Bow\Translate;

use BadMethodCallException;
use Bow\Support\Arraydotify;
use Iterator;

class Translator
{
    /**
     * The define language
     *
     * @var string
     */
    private static string $lang;

    /**
     * The lang directory
     *
     * @var string
     */
    private static string $directory;

    /**
     * The Translator instance
     *
     * @var ?Translator
     */
    private static ?Translator $instance = null;

    /**
     * Translator constructor.
     *
     * @param string $lang
     * @param string $directory
     * @param bool $auto_detected
     */
    public function __construct(string $lang, string $directory, bool $auto_detected = false)
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
    public static function configure(string $lang, string $directory): Translator
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
    public static function getInstance(): Translator
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
    public static function isLocale(string $locale): bool
    {
        return static::$lang == $locale;
    }

    /**
     * Make singleton translation
     *
     * @param string $key
     * @param array $data
     *
     * @return string
     */
    public static function single(string $key, array $data = []): string
    {
        return static::translate($key, $data);
    }

    /**
     * Allows translation
     *
     * @param string $key
     * @param array $data
     * @param bool $plural
     *
     * @return string
     */
    public static function translate(string $key, array $data = [], bool $plural = false): string
    {
        $map = explode('.', $key);

        if (count($map) == 1) {
            return $key;
        }

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

        if ($plural === true) {
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
     * Str formatter
     *
     * @param string $str
     * @param array $values
     * @return string
     */
    private static function format(string $str, array $values = []): string
    {
        foreach ($values as $key => $value) {
            if (is_array($value) || is_object($value) || $value instanceof Iterator) {
                $value = json_encode($value);
            }
            $str = preg_replace('/{\s*' . $key . '\s*\}/', (string)$value, $str);
        }

        return $str;
    }

    /**
     * Make plural translation
     *
     * @param string $key
     * @param array $data
     * @return string
     */
    public static function plural(string $key, array $data = []): string
    {
        return static::translate($key, $data, true);
    }

    /**
     * Update locale
     *
     * @param string $locale
     */
    public static function setLocale(string $locale): void
    {
        static::$lang = $locale;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return static::$lang;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new BadMethodCallException('Undefined method ' . $name);
    }
}
