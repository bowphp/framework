<?php
namespace Bow;

/**
 * Class BowAutoload, systeme de Chargement automatique des classes.
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow
 */
class BowAutoload
{
    /**
     * Charge le fichier original de la classe
     *
     * @param $class
     * @return void
     */
    private static function load($class)
    {
        $class = str_replace("\\", "/", $class);
        $class = preg_replace("~Bow/~", "src/", $class);
        $class = dirname(__DIR__). "/" . $class . ".php";

        if (is_file($class)) {
            require $class;
        }
    }

    /**
     * Launce l'autoload
     *
     * @param bool $helper
     * @return void
     */
    public static function register($helper = true)
    {
        spl_autoload_register([__CLASS__, 'load']);

        if ($helper) {
            require_once __DIR__ . "/Support/helper.php";
        }
    }
}
