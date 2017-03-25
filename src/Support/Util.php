<?php
namespace Bow\Support;

use Bow\Exception\UtilException;
use Bow\Exception\RouterException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Class Util
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Util
{
    /**
     * définir le type de retoure chariot CRLF ou LF
     * @var string
     */
    private static $sep;

    /**
     * setTimeZone, modifie la zone horaire.
     *
     * @param string $zone
     *
     * @throws \ErrorException
     */
    public static function setTimezone($zone)
    {
        if (count(explode('/', $zone)) != 2) {
            throw new UtilException('La définition de la zone est invalide');
        }

        date_default_timezone_set($zone);
    }

    /**
     * Lance un var_dump sur les variables passées en paramètre.
     *
     * @return void
     */
    public static function debug()
    {
        $vars = func_get_args();

        $cloner = new VarCloner();
        $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();

        $dumper->setStyles([
            'default' => 'background-color:#fff; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: normal',
            'num' => 'font-weight:bold; color:#1299DA',
            'const' => 'font-weight:bold',
            'str' => 'color:#111111',
            'note' => 'color:#1299DA',
            'ref' => 'color:#A0A0A0',
            'public' => 'color:blue',
            'protected' => 'color:#111',
            'private' => 'color:#478',
            'meta' => 'color:#B729D9',
            'key' => 'color:#212',
            'index' => 'color:#1200DA',
        ]);

        $handler = function ($vars) use ($cloner, $dumper) {
            foreach($vars as $var) {
                $dumper->dump($cloner->cloneVar($var));
            }
        };

        call_user_func_array($handler, [$vars]);
        die;
    }

    /**
     * Lance un var_dump sur les variables passées en paramètre.
     *
     * @param string $var
     * @return void
     */
    public static function dd($var)
    {
        call_user_func_array([static::class, 'dump'], func_get_args());
        die();
    }

    /**
     * sep, séparateur \r\n or \n
     *
     * @return string
     */
    public static function sep()
    {
        if (static::$sep !== null) {
            return static::$sep;
        }

        if (defined('PHP_EOL')) {
            static::$sep = PHP_EOL;
        } else {
            static::$sep = (strpos(PHP_OS, 'WIN') === false) ? '\n' : '\r\n';
        }

        return static::$sep;
    }
}
