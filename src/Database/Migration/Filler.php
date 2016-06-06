<?php
namespace Bow\Database\Migration;

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
        return "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
    }

    /**
     * @return string
     */
    public static function date()
    {
        return date("Y-d-m H:i:s");
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
}
