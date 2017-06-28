<?php
namespace Bow\Support;

class Faker
{
    /**
     * @var int
     */
    private static $autoincrement = 0;

    /**
     * @var array
     */
    const NAMES = [
        'John Doe', 'Bar Baz', 'Coulibaly Issa', 'Lamine Barro', 'Franck Dakia', 'Alix Brou', 'Maurice La',
        'Elodie Kra', 'John Davy', 'Bernard Kala', 'Kata Tourien', 'Victor Mondiata', 'Frédéric Kalan', 'Jacques Koné',
        'Arsène La', 'Jean-Eudes Brou', 'Armel Dakia', 'Florent Tuo', 'Alain Koussmé', 'Armand Koffi', 'Lucien Manda'
    ];

    /**
     * @var array
     */
    const EMAILS = [
        'johndoe@exemple.com', 'foobar@exemple.com',
        'barzar@exemple.com', 'luc@exemple.com',
        'claude@exemple.com', 'andrew@exemple.com',
        'audre@exemple.com', 'bow@exemple.com'
    ];

    /**
     * @var array
     */
    const TAGS = [
        'php', 'js', 'marketing', 'aws', 'informatique', 'tutoriel', 'réunion',
        'assurances', 'c#', 'web', 'python', 'c++', 'ruby', 'rails', 'pascal',
        'java', 'javascript', 'closure', 'hackaton', 'street', 'pays', 'la paix',
        'vivre', 'santé', 'virus', 'bot', 'arduino', 'rasberypy', 'bluemix', 'cloud',
        'djongo', ''
    ];

    /**
     * @var array
     */
    private static $selections = [];

    /**
     * @param array $additionnal_names
     * @param bool $random
     * @return string
     */
    public static function name(array $additionnal_names = [], $random = false)
    {
        if (is_bool($additionnal_names)) {
            $random = $additionnal_names;
            $additionnal_names = [];
        }

        $names = array_merge(self::NAMES, $additionnal_names);

        if ($random) {
            return $names[rand(0, count($names) - 1)];
        }

        return static::gen($names, 'name');
    }

    /**
     * @param array $additionnal
     * @return mixed
     */
    public static function pseudo(array $additionnal = [])
    {
        return Str::replace('/\s+/', '', Str::lower(static::name($additionnal)));
    }

    /**
     * @return mixed
     */
    public static function password()
    {
        $passwords = array_map(function($name) {
            return str_replace(' ', '', strtolower($name));
        }, self::NAMES);

        return static::gen($passwords, 'password');
    }

    /**
     * @param int $end
     * @return int
     */
    public static function number($end = 100)
    {
        return rand(1, $end);
    }

    /**
     * @param int $size
     * @param int $multi
     * @return string
     */
    public static function string($size = 255, $multi = 1)
    {
        if ($size == null) {
            $size = 255;
        }

        $str = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
        return Str::slice(Str::repeat($str, $multi), 0, $size);
    }

    /**
     * @param int $time
     * @return string
     */
    public static function date($time = null)
    {
        if ($time == null) {
            $time = 0;
        }

        return date("Y-m-d H:i:s", time() + $time);
    }

    /**
     * @param bool $negation
     * @return float
     */
    public static function float($negation = false)
    {
        $a = rand(1, 100) . "." . rand(1, 100);

        if ($negation) {
            $a = '-'.$a;
        }

        return (float) $a;
    }

    /**
     * @return int
     */
    public static function timestamps()
    {
        return time();
    }

    /**
     * @param array $additionnal_emails
     * @param bool $random
     * @return string
     */
    public static function email(array $additionnal_emails = [], $random = false)
    {
        $emails = array_merge(self::EMAILS, $additionnal_emails);

        if ($random) {
            return $emails[rand(0, count($emails) - 1)];
        }

        return static::gen($emails, 'email');
    }

    /**
     * @param string $type
     * @return string
     */
    public static function unique($type)
    {
        if ($type == 'string') {
            return Str::lower(uniqid());
        }
        return static::autoincrement('integer');
    }

    /**
     * @param string $type
     * @param int $start
     * @return string
     */
    public static function autoincrement($type = 'integer', $start = 0)
    {
        if (is_int($type)) {
            $start = $type;
            $type = 'integer';
        }
        if ($type != 'integer') {
            return null;
        }
        if (static::$autoincrement == 0) {
            static::$autoincrement = $start;
        }
        return ++static::$autoincrement;
    }

    /**
     * Permet de réinitialiser le faker
     */
    public static function reinitialize()
    {
        static::$autoincrement = 0;
        static::$selections = [];
    }

    /**
     * @param int $by
     * @return string
     */
    public static function tags($by = 2)
    {
        $tags = [];
        while (count($tags) <= $by) {
            $tag = static::TAGS[rand(0, $by)];
            if (!in_array($tag, $tags)) {
                $tags[] = static::TAGS[
                rand(0, count(static::TAGS) - 1)
                ];
            }
        }

        return implode(', ', $tags);
    }

    /**
     * @param $data
     * @param $key
     * @return mixed
     */
    private static function gen($data, $key)
    {
        if (!isset(static::$selections[$key])) {
            static::$selections[$key] = [];
        }

        $gen = $data[0];

        while (in_array($gen , static::$selections[$key])) {
            $gen = $data[rand(0, count($data) - 1)];
        }

        return static::$selections[$key][] = $gen;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (method_exists(Faker::class, $name)) {
            return call_user_func_array([Faker::class, $name], $arguments);
        }

        return null;
    }
}
