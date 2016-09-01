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
     * @var array
     */
    private static $names = [];

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
     * Lanceur de callback
     *
     * @param callable $cb
     * @param mixed $param
     * @param array $names
     * @throws RouterException
     * @return mixed
     */
    public static function launchCallback($cb, $param = null, array $names = [])
    {

        $param = is_array($param) ? $param : [$param];
        $function_list = [];

        if (!isset($names['namespace'])) {
            return static::execute_function($cb, $param);
        }

        static::$names = $names;

        if (!file_exists($names['autoload'] . '.php')) {
            throw new RouterException('L\'autoload n\'est pas défini dans le fichier de configuration', E_ERROR);
        }


        if (!isset($names['namespace']['app'])) {
            throw new RouterException('Le namespace d\'autoload n\'est pas défini dans le fichier de configuration');
        }

        $middleware = null;

        if (is_callable($cb)) {
            return call_user_func_array($cb, $param);
        }

        if (is_string($cb)) {
            return call_user_func_array(static::loadController($cb), $param);
        }

        if (is_array($cb)) {
            if (array_key_exists('middleware', $cb)) {
                $middleware = $cb['middleware'];
                unset($cb['middleware']);
            }

            if (array_key_exists('uses', $cb)) {
                if (is_array($cb['uses'])) {
                    if (isset($cb['uses']['with']) && isset($cb['uses']['call'])) {
                        if (is_string($cb['uses']['call'])) {
                            $controller = $cb['uses']['with'] . '@' . $cb['uses']['call'];
                            array_push($function_list, static::loadController($controller));
                        } else {
                            foreach($cb['uses']['call'] as $method) {
                                $controller = $cb['uses']['with'] . '@' . $method;
                                array_push($function_list,  static::loadController($controller));
                            }
                        }
                    } else {
                        foreach($cb['uses'] as $controller) {
                            if (is_string($controller)) {
                                array_push($function_list,  static::loadController($controller));
                            } else if (is_callable($controller)) {
                                array_push($function_list, $controller);
                            }
                        }
                    }
                } else {
                    if (is_string($cb['uses'])) {
                        array_push($function_list, static::loadController($cb['uses']));
                    } else {
                        array_push($function_list, $cb['uses']);
                    }
                }

                unset($cb['uses']);
            }

            if (count($cb) > 0) {
                foreach($cb as $func) {
                    if (is_callable($func)) {
                        array_push($function_list, $func);
                    } else if (is_string($func)) {
                        array_push($function_list, static::loadController($func));
                    }
                }
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        // Execution du middleware si define.
        if (is_string($middleware)) {
            if (!in_array(ucfirst($middleware), $names['middlewares'], true)) {
                throw new RouterException($middleware . ' n\'est pas un middleware définir.', E_ERROR);
            }

            // Chargement du middleware
            $classMiddleware = $names['namespace']['middleware'] . '\\' . ucfirst($middleware);

            // On vérifie si le middleware définie est une middleware valide.
            if (!class_exists($classMiddleware)) {
                throw new RouterException($middleware . ' n\'est pas un class Middleware.');
            }

            $instance = new $classMiddleware();
            $handler = [$instance, 'handle'];
            $status = call_user_func_array($handler, $param);

            // Le middleware est un callback. les middleware peuvent être// définir comme des callback par l'utilisteur
        } else if (is_callable($middleware)) {
            $status = call_user_func_array($middleware, $param);
        }

        // On arrêt tout en case de status false.
        if ($status == false) {
            return false;
        }

        // Lancement de l'execution de la liste
        // fonction a execute suivant un ordre
        // conforme au middleware.
        if (!empty($function_list)) {
            $status = true;

            foreach($function_list as $func) {
                $status = call_user_func_array($func, $param);
                if ($status == false) {
                    return $status;
                }
            }
        }

        return $status;
    }

    /**
     * Next, lance successivement une liste de fonction.
     *
     * @param array|callable $arr
     * @param array|callable $arg
     * @return mixed|void
     */
    private static function execute_function($arr, $arg)
    {
        if (is_callable($arr)) {
            return call_user_func_array($arr, $arg);
        }

        if (!is_array($arr)) {
            // On lance la loader de controller si $cb est un String
            $cb = static::loadController($arr);

            if ($cb !== null) {
                return call_user_func_array($cb, $arg);
            }

            return null;
        }

        // Lancement de la procedure de lancement recursive.
        array_reduce($arr, function($next, $cb) use ($arg) {
            // $next est-il null
            if (is_null($next)) {
                // On lance la loader de controller si $cb est un String
                if (is_string($cb)) {
                    $cb = static::loadController($cb);
                }

                return call_user_func_array($cb, $arg);
            }

            // $next est-il a true.
            if ($next !== true) {
                die();
            }

            // On lance la loader de controller si $cb est un String
            if (is_string($cb)) {
                $cb = static::loadController($cb);
            }

            return call_user_func_array($cb, $arg);
        });
    }

    /**
     * Charge les controlleurs
     *
     * @param string $controllerName. Le nom du controlleur a utilisé
     *
     * @return array
     */
    private static function loadController($controllerName)
    {
        // Récupération de la classe et de la methode à lancer.
        if (is_null($controllerName)) {
            return null;
        }

        list($class, $method) = preg_split('/\.|@/', $controllerName);
        $class = static::$names['namespace']['controller'] . '\\' . ucfirst($class);

        return [new $class(), $method];
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
     * systeme de débugage avec message d'info
     *
     * @param string $message
     * @param callable $cb
     *
     * @return void
     */
    public static function it($message, $cb = null)
    {
        echo '<h2>' . $message . '</h2>';

        if (is_callable($cb)) {
            call_user_func_array($cb, [static::class]);
        } else {
            static::debug(array_slice(func_get_args(), 1, func_num_args()));
        }
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
