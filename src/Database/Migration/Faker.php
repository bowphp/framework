<?php
namespace Bow\Database\Migration;

use Bow\Support\Str;

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
    private static $selections = [];

    /**
     * @param array $additionnal_names
     * @param bool $random
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

        $name = $names[0];
        while (in_array($name, static::$selections)) {
            $name = $names[rand(0, count($names) - 1)];
        }
        static::$selections[] = $name;
        return $name;
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

        $password = $passwords[0];

        while (in_array($password, static::$selections)) {
            $password = $passwords[rand(0, count($passwords) - 1)];
        }

        static::$selections[] = $password;

        return $password;
    }

    /**
     * @return int
     */
    public static function number()
    {
        return rand(1, 100);
    }

    /**
     * @param int $size
     * @param int $multi
     * @return string
     */
    public static function string($size = 255, $multi = 1)
    {
        $str = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
        return Str::slice(Str::repeat($str, $multi), 0, $size);
    }

    /**
     * @return string
     */
    public static function date()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * @return float
     */
    public static function float()
    {
        $a = rand(1, 100) . "." . rand(1, 100);
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
     * @return string
     */
    public static function email(array $additionnal_emails = [])
    {
        $emails = array_merge(self::EMAILS, $additionnal_emails);
        $email = $emails[0];

        while (in_array($email, static::$selections)) {
            $email = $emails[rand(0, count($emails) - 1)];
        }

        static::$selections[] = $email;

        return $email;
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
        if ($type != 'integer') {
            return null;
        }
        if (static::$autoincrement == 0) {
            static::$autoincrement = $start;
        }
        return ++static::$autoincrement;
    }
}
