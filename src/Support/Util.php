<?php

namespace Bow\Support;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Util
{
    /**
     * Define the CRLF or LF carriage return type
     *
     * @var string
     */
    private static $sep;

    /**
     * Run a var_dump on the variables passed in parameter.
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
            if (!is_array($vars)) {
                $vars = [$vars];
            }

            foreach ($vars as $var) {
                $dumper->dump($cloner->cloneVar($var));
            }
        };

        call_user_func_array($handler, [$vars]);
    }

    /**
     * Run a var_dump on the variables passed in parameter.
     *
     * @param  string $var
     * @return void
     */
    public static function dd($var)
    {
        call_user_func_array([static::class, 'debug'], func_get_args());

        die();
    }

    /**
     * sep, separator \r\n or \n
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


    /**
     * Function to secure the data.
     *
     * @param array $data, les données à sécuriser
     *
     * @return string $field
     */
    public static function rangeField($data)
    {
        $field = '';
        $i = 0;

        foreach ($data as $key => $value) {
            $field .= ($i > 0 ? ', ' : '') . '`'.$key . '` = ' . $value;

            $i++;
        }

        return $field;
    }

    /**
     * Data trainer. key => :value
     *
     * @param array $data
     * @param bool  $byKey
     *
     * @return array $resultat
     */
    public static function add2points(array $data, $byKey = false)
    {
        $resultat = [];

        if ($byKey == true) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $resultat[$key] = ':' . $value;
                } else {
                    $resultat[$key] = '?';
                }
            }
        } else {
            foreach ($data as $key => $value) {
                $resultat[$value] = ':' . $value;
            }
        }
        
        return $resultat;
    }
}
