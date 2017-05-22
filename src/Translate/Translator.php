<?php
namespace Bow\Translate;

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
     * Permet de faire la tranduction
     *
     * @param string $translation
     * @param array $data
     * @param int $choose
     * @return string
     */
    public static function make($translation, $data = [], $choose = null)
    {
        if (! is_string($translation)) {
            throw new \InvalidArgumentException('La premier parametre doit etre une chaine de carractère.', E_USER_ERROR);
        }

        if (is_int($data)) {
            $choose = $data;
            $data = [];
        }

        $base_dir = static::$directory.'/'.static::$lang;
        $map = explode('.', $translation);

        if (count($map) == 1) {
            return $translation;
        }

        $translation_filename = $base_dir.'/'.$map[0].'.php';

        if (! file_exists($translation_filename)) {
            return $translation;
        }

        $translation_contents = require $translation_filename;

        if (! isset($translation_contents[$map[1]])) {
            return $translation;
        }

        $translation_contents = $translation_contents[$map[1]];

        if (! is_string($translation_contents)) {
            return $translation;
        }

        if (is_int($choose)) {
            list($single, $pluriel) = explode('|', $translation_filename);

            if ($choose > 1 && is_string($pluriel)) {
                $translation_contents = $pluriel;
            } else {
                $translation_contents = $single;
            }
        }

        return static::format($translation_contents, $data);
    }

    /**
     * Permet de formater
     *
     * @param $str
     * @param array $values
     * @return string
     */
    private static function format($str, array $values)
    {
        foreach ($values as $key => $value) {
            $str = str_replace(':'.$key, $value, $str);
        }

        return $str;
    }
}