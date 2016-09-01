<?php
namespace Bow\Database\Migration;

use Bow\Support\Str;

class Filler
{
    /**
     * @return int
     */
    public static function number()
    {
        return rand(1, 100);
    }

    /**
     * @return string
     */
    public static function string()
    {
        return Str::shuffleWords("Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");
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
     * @return string
     */
    public static function email()
    {
        $emails = [
            'johndoe@exemple',
            'foobar@exemple',
            'barzar@exemple',
            'luc@exemple',
            'claude@exemple',
            'andrew@exemple',
            'audre@exemple'
        ];
        return $emails[rand(0, count($emails) - 1)];
    }

    /**
     * @return string
     */
    public static function unique()
    {
        return Str::lower(uniqid());
    }
}
